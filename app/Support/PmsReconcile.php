<?php

namespace App\Support;

use App\Booking;
use App\Listing;
use App\Models\PmsDifference;
use App\Models\PmsReconcileRun;
use App\OtherModel\EzeeBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * The PMS check (Sam, 15 Oct 2026). Every live eZee reservation whose stay
 * touches the window is compared with its Homemoka booking on four things:
 * unit, dates, status, amount. Homemoka bookings with no eZee record at all
 * are listed too. Each difference is kept in pms_differences until a person
 * resolves it or a later run no longer finds it. Nothing here changes a
 * booking; the daily reconcile (EzeeAutoAssign) and people do that.
 *
 * Why: on 23 Sep 2026 eZee moved a no-show off Bell Suites 07-30 and Homemoka
 * only reflected it on 5 Oct, after the owner had seen the higher figure.
 */
class PmsReconcile
{
    public const KINDS = [
        'A' => 'Reservation not in Homemoka',
        'B' => 'Cancelled here, still live in eZee',
        'C' => 'eZee on an extra room, booking on a unit (no-show?)',
        'D' => 'Different unit',
        'E' => 'Dates differ',
        'F' => 'eZee room not mapped to a unit',
        'G' => 'Amount differs',
        'H' => 'Booking with no eZee record',
    ];

    /** Run the check for stays touching [$from, $to]. Returns the run row. */
    public static function run(string $from, string $to, string $scope = 'daily', ?string $month = null, ?int $userId = null): PmsReconcileRun
    {
        $t0    = microtime(true);
        $now   = now();
        $found = [];   // fingerprint => attributes

        $map   = EzeeUnitMap::make();
        $auto  = new EzeeAutoAssign(true, null);
        $drift = (new ReflectionClass($auto))->getMethod('amountDrift');
        $drift->setAccessible(true);

        $isExtra = fn (?Listing $l) => $l && stripos((string) $l->name, 'Extra Room') !== false;
        $monthOf = fn ($date) => substr((string) $date, 0, 7);

        // eZee side: live reservations overlapping the window
        $rows = EzeeBooking::where('status', '<>', 1)
            ->where('Start', '<=', $to)->where('End', '>', $from)
            ->whereNotNull('RoomName')->where('RoomName', '<>', '')
            ->where('SubBookingId', 'not like', 'NORES%')   // rows built from report uploads are not eZee reservations
            ->orderBy('Start')->get()
            // eZee keeps a few dead rows from 2022–2025 with an End years later; a real stay is at most a year.
            ->filter(fn ($eb) => Carbon::parse($eb->Start)->diffInDays(Carbon::parse($eb->End)) <= 400)->values();

        foreach ($rows as $eb) {
            $b   = $eb->book_id ? Booking::withoutGlobalScopes()->find($eb->book_id) : null;
            $add = function (string $kind, string $detail, ?Listing $unit = null) use (&$found, $eb, $b, $monthOf) {
                $fp = $kind . ':' . $eb->SubBookingId;
                $found[$fp] = [
                    'kind' => $kind, 'sub_booking_id' => $eb->SubBookingId, 'ezee_booking_id' => $eb->id, 'booking_id' => $b?->id,
                    'listing_id' => $unit?->id ?? $b?->listing_id, 'unit' => $unit?->name ?? $b?->listing?->name ?? $eb->RoomName,
                    'stay_from' => $eb->Start, 'stay_to' => $eb->End, 'month' => $monthOf($eb->Start), 'detail' => mb_substr($detail, 0, 255),
                ];
            };

            if (!$b) {
                $add('A', trim(($eb->ezee_current_status ?: 'no status') . ' · ' . $eb->RoomName . ' · RM ' . number_format((float) $eb->TotalAmountAfterTax, 2)));
                continue;
            }
            if ((int) $b->status === 1) {
                $add('B', 'Booking #' . $b->id . ' is cancelled here; eZee shows ' . ($eb->ezee_current_status ?: 'live'));
                continue;
            }
            $chain = collect(BookingSplitter::stayChain($b, substr((string) $eb->TransactionId, 0, 5)))->filter(fn ($x) => (int) $x->status !== 1);
            $l = $map->resolve($eb);
            if (!$l && preg_match('/^(.*?)\s*\(.*\)\s*$/', (string) $eb->RoomName, $m)) {
                // eZee carries duplicate rooms for one unit: "BS 07-30 (No Monthly)", "H-23-3A (Rental Unit)", "C2-07-10 (With Karaoke)".
                $code = EzeeUnitMap::compact(trim($m[1]));
                $l = $map->listingForUnitName(trim($m[1]));
                if (!$l && $code !== '' && $b->listing && str_ends_with(EzeeUnitMap::compact((string) $b->listing->name), $code)) {
                    $l = $b->listing;   // the booking already sits on the unit the room name spells
                }
            }
            if (!$l) {
                $add('F', 'eZee room "' . $eb->RoomName . '" has no Homemoka unit; booking #' . $b->id . ' sits on ' . ($b->listing?->name ?? '?'));
                continue;
            }
            $onIt = $chain->contains(fn ($x) => (int) $x->listing_id === (int) $l->id);
            if (!$onIt) {
                $unit = Listing::withoutGlobalScope('notArchived')->find($b->listing_id);
                if ($isExtra($l) && $isExtra($unit)) {
                    // one extra room is as good as another
                } elseif ((string) $eb->Start === (string) $eb->End && $isExtra($unit)) {
                    // day use (hourly) is parked on an extra room on purpose: company revenue, not the owner's
                } elseif ($isExtra($l)) {
                    $add('C', 'Booking #' . $b->id . ' on ' . ($unit?->name ?? '?') . '; eZee moved the stay to ' . $l->name, $unit);
                } else {
                    $add('D', 'Booking #' . $b->id . ' on ' . ($unit?->name ?? '?') . '; eZee says ' . $l->name, $unit);
                }
                continue;
            }
            $cin  = (string) $chain->min('check_in');
            $cout = (string) $chain->max('check_out');
            if ($cin !== (string) $eb->Start || $cout !== (string) $eb->End) {
                $add('E', 'Here ' . $cin . ' → ' . $cout . '; eZee ' . $eb->Start . ' → ' . $eb->End . ' (booking #' . $b->id . ')', $l);
                continue;
            }
            try {
                $d = $drift->invoke($auto, $eb, $b);
            } catch (\Throwable $e) {
                $d = null;
            }
            if ($d && !$d['room_differs'] && preg_match('/-\d+$/', (string) $eb->SubBookingId)) {
                $d = null;   // a "-1"/"-2" split carries the folio's cleaning on one row only
            }
            if ($d) {
                $add('G', sprintf('Room here RM %s, eZee RM %s%s (booking #%d)', number_format($d['ours']['room'], 2), number_format($d['theirs']['room'], 2), $d['clean_differs'] ? '; cleaning differs' : '', $b->id), $l);
            }
        }

        // Homemoka side: live bookings in the window whose whole folio family has no live eZee row.
        $linked = EzeeBooking::where('status', '<>', 1)->whereNotNull('book_id')->pluck('book_id');
        $bookings = Booking::with('listing')->where('status', '<>', 1)
            ->where('check_in', '<=', $to)->where('check_out', '>', $from)->get();
        $linkedFolios = Booking::whereIn('id', $linked)->whereNotNull('folio_no')->where('folio_no', '<>', '')
            ->get(['folio_no', 'listing_id'])->map(fn ($x) => self::folioKey($x->folio_no, $x->listing_id))->flip();
        foreach ($bookings as $b) {
            if ($linked->contains($b->id)) {
                continue;
            }
            if ($b->folio_no && isset($linkedFolios[self::folioKey($b->folio_no, $b->listing_id)])) {
                continue;   // a segment of a stay that is linked through another row
            }
            $chainIds = collect(BookingSplitter::stayChain($b))->pluck('id');
            if ($chainIds->count() > 1 && $chainIds->intersect($linked)->isNotEmpty()) {
                continue;   // split stay or month segment whose sibling carries the eZee link
            }
            if ($b->folio_no && EzeeBooking::where('status', '<>', 1)->where('folio_no', $b->folio_no)
                    ->where('Start', '<=', $b->check_out)->where('End', '>=', $b->check_in)->exists()) {
                continue;   // eZee has a live row on this folio over these dates; the link sits on another segment
            }
            $fp = 'H:b' . $b->id;
            $found[$fp] = [
                'kind' => 'H', 'sub_booking_id' => null, 'ezee_booking_id' => null, 'booking_id' => $b->id,
                'listing_id' => $b->listing_id, 'unit' => $b->listing?->name,
                'stay_from' => $b->check_in, 'stay_to' => $b->check_out, 'month' => $monthOf($b->check_in),
                'detail' => mb_substr(trim(($b->source ?: 'no source') . ' · ' . ($b->folio_no ? 'folio ' . $b->folio_no : 'no folio') . ($b->remark ? ' · ' . $b->remark : '')), 0, 255),
            ];
        }

        // Upsert: new ones open; seen-again ones refresh; a manual resolution sticks.
        $new = 0;
        $existing = PmsDifference::whereIn('fingerprint', array_keys($found))->get()->keyBy('fingerprint');
        foreach ($found as $fp => $attrs) {
            $row = $existing->get($fp);
            if (!$row) {
                PmsDifference::create($attrs + ['fingerprint' => $fp, 'first_seen_at' => $now, 'last_seen_at' => $now]);
                $new++;
                continue;
            }
            $upd = ['last_seen_at' => $now, 'detail' => $attrs['detail'], 'booking_id' => $attrs['booking_id'], 'listing_id' => $attrs['listing_id'], 'unit' => $attrs['unit']];
            if ($row->resolved_at && $row->auto_resolved) {
                $upd += ['resolved_at' => null, 'resolved_by' => null, 'resolution' => null, 'auto_resolved' => false];   // came back
                $new++;
            }
            $row->update($upd);
        }

        // Open differences inside the window that this run did not find have been cleared by the data.
        $cleared = PmsDifference::open()->where('stay_from', '<=', $to)->where('stay_to', '>', $from)
            ->whereNotIn('fingerprint', array_keys($found) ?: ['-'])
            ->update(['resolved_at' => $now, 'resolution' => 'No longer present: cleared by the data', 'auto_resolved' => true]);

        return PmsReconcileRun::create([
            'scope' => $scope, 'month' => $month, 'from' => $from, 'to' => $to,
            'reservations' => $rows->count(), 'bookings' => $bookings->count(),
            'open' => PmsDifference::open()->where('stay_from', '<=', $to)->where('stay_to', '>', $from)->count(),
            'new' => $new, 'cleared' => $cleared, 'user_id' => $userId,
            'duration_ms' => (int) round((microtime(true) - $t0) * 1000), 'ran_at' => $now,
        ]);
    }

    /** Daily: stays touching the last N days. */
    public static function daily(int $days = 60, ?int $userId = null): PmsReconcileRun
    {
        return self::run(now()->subDays($days)->toDateString(), now()->addDays(1)->toDateString(), 'daily', null, $userId);
    }

    /** Month-end: every stay touching the month. */
    public static function month(string $ym, ?int $userId = null): PmsReconcileRun
    {
        $start = Carbon::createFromFormat('Y-m-d', $ym . '-01')->startOfMonth();
        return self::run($start->toDateString(), $start->copy()->endOfMonth()->toDateString(), 'month', $ym, $userId);
    }

    public static function openCount(?string $month = null): int
    {
        return PmsDifference::open()->when($month, fn ($q) => $q->where('month', $month))->count();
    }

    public static function latestRun(): ?PmsReconcileRun
    {
        return PmsReconcileRun::orderByDesc('id')->first();
    }

    /**
     * Month-end gate for the owner-report run: a month check within the last
     * 24 hours with no open difference for that month.
     * @return array{ok: bool, run: ?PmsReconcileRun, open: int, why: ?string}
     */
    public static function gate(string $ym): array
    {
        $run  = PmsReconcileRun::where('scope', 'month')->where('month', $ym)->orderByDesc('id')->first();
        $open = self::openCount($ym);
        if (!$run) {
            return ['ok' => false, 'run' => null, 'open' => $open, 'why' => 'The PMS check has not been run for this month yet.'];
        }
        if ($run->ran_at->lt(now()->subHours(24))) {
            return ['ok' => false, 'run' => $run, 'open' => $open, 'why' => 'The last PMS check for this month was on ' . $run->ran_at->format('j M H:i') . '; run it again before sending.'];
        }
        if ($open > 0) {
            return ['ok' => false, 'run' => $run, 'open' => $open, 'why' => $open . ' PMS difference' . ($open === 1 ? '' : 's') . ' still open for this month.'];
        }
        return ['ok' => true, 'run' => $run, 'open' => 0, 'why' => null];
    }

    private static function folioKey(string $folio, $listingId): string
    {
        // Folios repeat across hotels; the hotel is implied by the unit's property prefix.
        $l = Listing::withoutGlobalScope('notArchived')->find($listingId);
        $prop = $l ? strtolower(trim(explode(' ', (string) $l->name)[0])) : '';
        return $prop . '|' . strtoupper(trim($folio));
    }
}

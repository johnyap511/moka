<?php

namespace App\Support;

use App\Booking;
use App\Listing;
use App\Models\PmsDifference;
use App\OtherModel\EzeeBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The month sheet (Sam, 6 Nov 2026): every Homemoka booking that checks in
 * during the month, next to its eZee record, with a verdict a person can act
 * on: OK, REVIEW or NOT OK, a reason in words and the thing to do. eZee
 * reservations with no Homemoka booking are listed at the end as EZEE ONLY.
 *
 * It is the advisory layer over the PMS check: the differences list says
 * what is wrong; this sheet says, booking by booking, why an amount differs
 * and whether that is fine. Nothing here writes; the rules are applied in
 * order and the first that fits wins, as in Sam's reconciliation tool (v6.7).
 */
class PmsMonthSheet
{
    public const OK = 'OK';
    public const REVIEW = 'REVIEW';
    public const NOT_OK = 'NOT OK';
    public const EZEE_ONLY = 'EZEE ONLY';

    /** @var array<int, Collection> chain members by booking id, filled as chains are walked */
    private array $chains = [];

    private array $cfg;
    private EzeeUnitMap $map;

    public function __construct()
    {
        $this->cfg = self::thresholds();
        $this->map = EzeeUnitMap::make();
    }

    /** Thresholds, from config so Finance can move them without a code change. */
    public static function thresholds(): array
    {
        $c = (array) config('moka.pms_check', []);
        return [
            'ltr_min_nights' => (int) ($c['ltr_min_nights'] ?? 300),
            'sst_rounding'   => (float) ($c['sst_rounding'] ?? 0.02),
            'rounding'       => (float) ($c['rounding'] ?? 0.50),
            'materiality'    => (float) ($c['materiality'] ?? 100),
            'deposit'        => (float) ($c['deposit'] ?? config('moka.refund_deposit', 200)),
        ];
    }

    /**
     * @return array{month: string, rows: array<int, array>, summary: array}
     */
    public static function build(string $ym): array
    {
        return (new self())->run($ym);
    }

    private function run(string $ym): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $ym . '-01')->startOfMonth();
        $from  = $start->toDateString();
        $to    = $start->copy()->endOfMonth()->toDateString();

        $bookings = Booking::with('listing')->where('status', '<>', 1)
            ->whereBetween('check_in', [$from, $to])
            ->orderBy('check_in')->get();
        $ids = $bookings->pluck('id');

        // eZee rows linked to these bookings, one query.
        $linked = EzeeBooking::where('status', '<>', 1)->whereIn('book_id', $ids)->get()->keyBy('book_id');

        // Open differences for the month, by booking (B–H) and by reservation (A).
        $open = PmsDifference::open()->where('month', $ym)->get();
        $openByBooking = $open->whereNotNull('booking_id')->groupBy('booking_id');
        $openA = $open->where('kind', 'A');

        $rows = [];
        foreach ($bookings as $b) {
            $rows[] = $this->classify($b, $linked, $openByBooking->get($b->id, collect()));
        }
        // Unit, then date: the order staff read the owner statements in.
        usort($rows, fn ($x, $y) => [$x['unit'], $x['check_in'], $x['booking_id']] <=> [$y['unit'], $y['check_in'], $y['booking_id']]);

        foreach ($openA as $d) {
            $rows[] = $this->ezeeOnly($d);
        }

        $summary = ['total' => count($rows)];
        foreach ([self::OK, self::REVIEW, self::NOT_OK, self::EZEE_ONLY] as $s) {
            $summary[$s] = count(array_filter($rows, fn ($r) => $r['status'] === $s));
        }
        $summary['ours'] = round(array_sum(array_map(fn ($r) => (float) ($r['ours'] ?? 0), $rows)), 2);
        // Each eZee reservation once, however many segments hold it here.
        $seen = [];
        $summary['theirs'] = 0.0;
        foreach ($rows as $r) {
            $key = $r['res'] ?: 'b' . $r['booking_id'];
            if ($r['theirs'] !== null && !isset($seen[$key])) {
                $seen[$key] = true;
                $summary['theirs'] += (float) $r['theirs'];
            }
        }
        $summary['theirs'] = round($summary['theirs'], 2);

        return ['month' => $ym, 'from' => $from, 'to' => $to, 'rows' => $rows, 'summary' => $summary, 'thresholds' => $this->cfg];
    }

    /** One Homemoka booking → its sheet row. */
    private function classify(Booking $b, Collection $linked, Collection $openDiffs): array
    {
        $unit    = $b->listing;
        $isExtra = $unit && stripos((string) $unit->name, 'Extra Room') !== false;
        $cin     = substr((string) $b->check_in, 0, 10);
        $cout    = substr((string) $b->check_out, 0, 10);
        $source  = (string) $b->source;
        $ours    = round((float) $b->price, 2);

        /** @var EzeeBooking|null $eb */
        $eb = $linked->get($b->id);
        $viaSibling = false;
        $chain = $this->chain($b, $eb ? substr((string) $eb->TransactionId, 0, 5) : null);
        if (!$eb) {
            foreach ($chain as $sib) {
                if ((int) $sib->id !== (int) $b->id && $linked->has($sib->id)) {
                    $eb = $linked->get($sib->id);
                    $viaSibling = true;
                    break;
                }
            }
            if (!$eb) {
                $sib = EzeeBooking::where('status', '<>', 1)->whereIn('book_id', $chain->pluck('id'))->first();
                if ($sib) {
                    $eb = $sib;
                    $viaSibling = true;
                }
            }
        }

        $row = [
            'booking_id' => $b->id, 'res' => $eb?->SubBookingId, 'folio' => $b->server_folio_no ?: $b->folio_no,
            'unit' => $unit?->name ?? '?', 'check_in' => $cin, 'check_out' => $cout, 'nights' => (int) $b->nights,
            'source' => $source, 'ezee_source' => $eb?->Source, 'ours' => $ours,
            'theirs' => null, 'diff' => null, 'stay_ours' => null, 'status' => self::OK, 'code' => '', 'reason' => '', 'action' => '',
            'extra_room' => $isExtra, 'segment' => $chain->count() > 1,
        ];
        $verdict = function (string $status, string $code, string $reason, string $action = '') use (&$row) {
            $row['status'] = $status;
            $row['code']   = $code;
            $row['reason'] = $reason;
            $row['action'] = $action;
            return $row;
        };
        $note = $isExtra ? 'Extra Room: company revenue, no owner share. ' : '';

        // 1. An open PMS difference outranks everything: it is already on the list to resolve.
        if ($openDiffs->isNotEmpty()) {
            $d = $openDiffs->sortBy('kind')->first();
            return $verdict(self::NOT_OK, 'DIFF-' . $d->kind, $note . 'PMS difference ' . $d->kind . ' (' . (PmsReconcile::KINDS[$d->kind] ?? '') . '): ' . $d->detail,
                'Resolve it on the PMS check list first.');
        }

        // 2. Nothing in eZee for this booking.
        if (!$eb) {
            if (stripos($source, 'owner') !== false) {
                return $verdict(self::OK, 'OWNER', $note . 'Owner booking; eZee may not carry it.');
            }
            if ($ours == 0.0) {
                return $verdict(self::OK, 'ZERO', $note . 'Zero-value booking with no eZee record.');
            }
            return $verdict(self::NOT_OK, 'NO-EZEE', $note . 'No live eZee record is linked to this booking' . ($b->folio_no ? ' (folio ' . $b->folio_no . ')' : '') . '.',
                'Find the folio in eZee and link it on the booking, or cancel the booking if the stay never happened.');
        }

        $bd      = EzeePricing::breakdown($eb);
        $theirs  = round((float) $bd['total'], 2);
        $ezNights = (int) $bd['nights'];
        $row['theirs'] = $theirs;
        $row['diff']   = round($ours - $theirs, 2);
        if ($chain->count() > 1) {
            // The eZee figure is the whole stay; the difference that matters is against all its rows.
            $row['stay_ours'] = round((float) $chain->sum('price'), 2);
            $row['diff'] = round($row['stay_ours'] - $theirs, 2);
        }

        // 3. Long-term rental on either side: the monthly rent is what it is.
        $ltr = fn ($s) => stripos((string) $s, 'long term') !== false;
        if ($ltr($source) || $ltr($eb->Source)) {
            $row['diff'] = null;   // monthly rent against a whole-tenancy figure: no comparison is made
            return $verdict(self::OK, 'LTR', $note . 'Long Term Rental' . ($ltr($source) && !$ltr($eb->Source) ? ' here; eZee source ' . $eb->Source : '') . '.');
        }

        // 4. A tenancy-length stay booked as a short stay.
        if ($ezNights > $this->cfg['ltr_min_nights']) {
            $row['diff'] = null;
            return $verdict(self::NOT_OK, 'WRONG-SOURCE', $note . 'eZee stay is ' . $ezNights . ' nights but the source is ' . ($eb->Source ?: 'blank') . ', not Long Term Rental.',
                'Correct the source in eZee (and here) so the 8% fee and owner share are right.');
        }

        // 5. Day use (hourly).
        if ($cin === $cout || (int) $b->nights === 0) {
            if ($isExtra) {
                return $verdict(self::OK, 'DAY-USE', 'Day use on an extra room: company revenue.');
            }
            return $verdict(self::REVIEW, 'DAY-USE', 'Day use (no night) on an owner unit; not billable to the owner.',
                'Confirm in eZee whether this is a real stay; day use normally sits on an extra room.');
        }

        // 6. Agreement, with the two rounding tolerances.
        $diff = $chain->count() > 1 ? round($ours - $theirs, 2) : $row['diff'];
        $segNote = $viaSibling || $chain->count() > 1 ? ' This booking is one segment of a stay held as ' . $chain->count() . ' rows.' : '';
        if ($chain->count() === 1 && abs($diff) <= $this->cfg['sst_rounding']) {
            return $verdict(self::OK, 'OK', $note . 'Agrees with eZee' . ($diff != 0 ? ' (SST rounding ' . number_format($diff, 2) . ')' : '') . '.');
        }
        if (abs($diff) < $this->cfg['rounding'] && $chain->count() === 1) {
            return $verdict(self::OK, 'ROUNDING', $note . 'Accumulated SST rounding of RM ' . number_format(abs($diff), 2) . '.');
        }

        // 7. A "-1"/"-2" row is one room of a multi-room reservation: the folio's cleaning sits on one row only.
        if (preg_match('/-\d+$/', (string) $eb->SubBookingId)) {
            $roomOurs = round((float) $b->price_night * (int) $b->nights, 2);
            $roomTheirs = round((float) $bd['price_night'] * $ezNights, 2);
            if (abs($roomOurs - $roomTheirs) <= max(1.0, 0.0051 * $ezNights)) {
                return $verdict(self::OK, 'SPLIT', $note . 'Split room ' . $eb->SubBookingId . ': room charge agrees; cleaning is carried on the sibling row.');
            }
        }

        // 8. A stay held as several rows (month segments or a room move): compare the whole stay.
        if ($chain->count() > 1) {
            $stayOurs = round((float) $chain->sum('price'), 2);
            $stayDiff = round($stayOurs - $theirs, 2);
            if (abs($stayDiff) < $this->cfg['rounding']) {
                return $verdict(self::OK, 'SEGMENT', $note . 'Segment RM ' . number_format($ours, 2) . ' of a stay of RM ' . number_format($stayOurs, 2) . ' (' . $chain->count() . ' rows); the whole stay agrees with eZee.');
            }
            $explained = $this->explain($stayDiff, $b, $eb, $bd, $chain);
            if ($explained) {
                return $verdict(self::OK, $explained[0], $note . 'Whole stay (' . $chain->count() . ' rows): ' . $explained[1]);
            }
            if (abs($stayDiff) < $this->cfg['materiality']) {
                return $verdict(self::OK, 'CROSS-MONTH', $note . 'Whole stay RM ' . number_format($stayOurs, 2) . ' vs eZee RM ' . number_format($theirs, 2) . ': difference RM ' . number_format($stayDiff, 2) . ' is below the RM ' . number_format($this->cfg['materiality'], 0) . ' materiality line.');
            }
            return $verdict(self::REVIEW, 'CROSS-MONTH', $note . 'Whole stay RM ' . number_format($stayOurs, 2) . ' vs eZee RM ' . number_format($theirs, 2) . ': unexplained RM ' . number_format($stayDiff, 2) . ' across ' . $chain->count() . ' rows.',
                'Open folio ' . ($eb->folio_no ?: $b->folio_no) . ' in eZee and tick the segments against the itemised charges.');
        }

        // 9. Explain the difference from eZee's own lines.
        $explained = $this->explain($diff, $b, $eb, $bd, $chain);
        if ($explained) {
            return $verdict(self::OK, $explained[0], $note . $explained[1]);
        }

        return $verdict(self::NOT_OK, 'UNEXPLAINED', $note . 'Here RM ' . number_format($ours, 2) . ', eZee RM ' . number_format($theirs, 2) . ': difference RM ' . number_format($diff, 2) . ' not explained by rounding, extras, cleaning or deposit.' . $segNote,
            'Open folio ' . ($eb->folio_no ?: $b->folio_no) . ' in eZee and compare the itemised list with booking #' . $b->id . '; correct whichever side is wrong.');
    }

    /**
     * Can eZee's own lines account for the difference? Returns [code, reason] or null.
     * Order: extras that are not revenue, the RM 200 deposit, a cleaning fee on the other side of a month end.
     */
    private function explain(float $diff, Booking $b, EzeeBooking $eb, array $bd, Collection $chain): ?array
    {
        $tol = $this->cfg['rounding'];
        $list = EzeePricing::extraChargeList($eb) ?? [];
        $extras = [];
        $extrasSum = 0.0;
        foreach ($list as $c) {
            $name = (string) ($c['ChargeName'] ?? $c['ChargeDesc'] ?? '');
            if ($name === '' || EzeePricing::isCleaningCharge($name)) {
                continue;
            }
            $amt = (float) ($c['AmountAfterTax'] ?? $c['Amount'] ?? 0);
            $extras[] = $name . ' RM ' . number_format($amt, 2);
            $extrasSum += $amt;
        }
        if ($extras && abs(abs($diff) - abs($extrasSum)) < $tol) {
            return ['EXTRAS', 'Difference is eZee extras that are not stay revenue: ' . implode(', ', $extras) . '.'];
        }
        $dep = $this->cfg['deposit'];
        if ($dep > 0 && abs(abs($diff) - $dep) < $tol) {
            return ['DEPOSIT', 'Difference is the RM ' . number_format($dep, 0) . ' security deposit held in eZee, not revenue.'];
        }
        $cfs = array_filter([(float) $b->cleaning_fee, (float) $bd['cleaning_fee'], (float) $chain->sum('cleaning_fee')]);
        foreach ($cfs as $cf) {
            if ($cf > 0 && (abs(abs($diff) - $cf) < $tol || abs(abs($diff) - $cf * 1.08) < $tol)) {
                return ['CLEANING', 'Difference is the cleaning fee RM ' . number_format($cf, 2) . ' (with or without SST), posted on the other side of a month end or on a sibling row.'];
            }
        }
        return null;
    }

    /** An eZee reservation with no Homemoka booking (open difference A), categorised the way Sam's tool does. */
    private function ezeeOnly(PmsDifference $d): array
    {
        $eb = $d->ezee_booking_id ? EzeeBooking::find($d->ezee_booking_id) : null;
        $l  = $eb ? $this->map->resolve($eb) : null;
        $isExtra = $l && stripos((string) $l->name, 'Extra Room') !== false;
        $dayUse  = $eb && (string) $eb->Start === (string) $eb->End;
        $total   = $eb ? round((float) $eb->TotalAmountAfterTax, 2) : null;
        $row = [
            'booking_id' => null, 'res' => $d->sub_booking_id, 'folio' => $eb?->folio_no,
            'unit' => $l?->name ?? $d->unit, 'check_in' => substr((string) $d->stay_from, 0, 10), 'check_out' => substr((string) $d->stay_to, 0, 10),
            'nights' => $eb ? EzeePricing::breakdown($eb)['nights'] : null, 'source' => null, 'ezee_source' => $eb?->Source,
            'ours' => null, 'theirs' => $total, 'diff' => null, 'stay_ours' => null, 'status' => self::EZEE_ONLY, 'extra_room' => $isExtra, 'segment' => false,
        ];
        if ($isExtra) {
            $row += ['code' => 'EZ-EXTRA', 'reason' => 'eZee stay on an extra room with no Homemoka booking: company revenue, not billable to an owner.', 'action' => 'Check it is a real stay; add it here only if the room is managed.'];
        } elseif ($dayUse) {
            $row += ['code' => 'EZ-DAYUSE', 'reason' => 'eZee day use with no Homemoka booking: not billable to the owner.', 'action' => 'Confirm with eZee; no owner report impact.'];
        } elseif ($total !== null && $total <= 0) {
            $row += ['code' => 'EZ-ZERO', 'reason' => 'eZee row with zero total and no Homemoka booking.', 'action' => 'Nothing to bill; resolve the difference with a note.'];
        } else {
            $row += ['code' => 'EZ-MISSING', 'reason' => 'eZee stay on an owner unit with no Homemoka booking: owner revenue missing from the report' . ($total !== null ? ' (RM ' . number_format($total, 2) . ')' : '') . '.', 'action' => 'Add the booking in Homemoka keyed by folio ' . ($eb?->folio_no ?: '?') . ', then resolve the difference.'];
        }
        return $row;
    }

    /** The live rows of the stay this booking belongs to, memoised across members. */
    private function chain(Booking $b, ?string $hotel): Collection
    {
        if (isset($this->chains[$b->id])) {
            return $this->chains[$b->id];
        }
        try {
            $chain = collect(BookingSplitter::stayChain($b, $hotel))->filter(fn ($x) => (int) $x->status !== 1)->values();
        } catch (\Throwable $e) {
            $chain = collect([$b]);
        }
        if ($chain->isEmpty()) {
            $chain = collect([$b]);
        }
        foreach ($chain as $m) {
            $this->chains[$m->id] = $chain;
        }
        return $chain;
    }
}

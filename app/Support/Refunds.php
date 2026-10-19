<?php

namespace App\Support;

use App\Booking;
use App\Listing;
use App\Models\RefundLink;
use App\Models\RefundRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Deposit refund portal.
 *
 * One link, valid 48 hours, shown on the admin dashboard and pasted into the
 * daily check-out message. The guest fills in the same questions as the old
 * Google Form. Matching to the booking is silent: the guest only ever sees what
 * they typed; Finance sees "matched" or "verify". Nothing is paid
 * automatically and no record is ever deleted.
 */
class Refunds
{
    public const LINK_HOURS = 48;
    public const WORKING_DAYS = 3;
    public const MAX_PER_PHONE_PER_DAY = 3;

    /** Property names the guest picks from, with the listing-name prefix each one maps to. */
    public const PROPERTIES = [
        'EkoCheras by MOKA'    => 'Ekocheras',
        'Bell Suites by MOKA'  => 'Bell Suites',
        'Forum SOHO by MOKA'   => 'Forum',
        'Alinea by MOKA'       => 'Alinea',
        'Arte Cheras by MOKA'  => 'Arte Cheras',
        'Queensville by MOKA'  => 'Queensville',
        'Damai 88 by MOKA'     => 'Damai 88',
        'Mesahill by MOKA'     => 'Mesahill',
    ];

    /** The link currently valid, or null. */
    public static function currentLink(): ?RefundLink
    {
        return RefundLink::where('expires_at', '>', now())->orderByDesc('expires_at')->first();
    }

    /** The current link, issuing a fresh one when none is valid. */
    public static function link(?int $userId = null, bool $fresh = false): RefundLink
    {
        if (!$fresh && ($link = self::currentLink())) {
            return $link;
        }
        return RefundLink::create([
            'token'      => Str::lower(Str::random(28)),
            'expires_at' => self::expiryFor(now()),
            'created_by' => $userId,
        ]);
    }

    /**
     * Links expire at 09:00, when the front desk starts sending the day's
     * check-out messages (Sam, 12 Oct 2026): the second 09:00 after issue. The
     * 09:00 job therefore issues links valid exactly 48 hours; one issued by
     * hand in the afternoon still lasts until the morning after next.
     */
    public static function expiryFor(Carbon $issued): Carbon
    {
        $nine = $issued->copy()->setTime(9, 0, 0);
        if ($nine->lte($issued)) {
            $nine->addDay();
        }
        return $nine->addDay();
    }

    /** Active Operation Team members a guest can name. */
    public static function staff()
    {
        // Shared or placeholder accounts (the "Intern" login) are not people a guest can name (Sam, 12 Oct 2026).
        $exclude = array_map('strtolower', config('moka.refund_staff_exclude', ['intern']));
        return User::whereIn('admin_role', ['operations', 'operations_manager'])
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'last_name'])
            ->reject(fn ($u) => in_array(strtolower(trim($u->name)), $exclude, true) || in_array(strtolower(trim($u->name . ' ' . $u->last_name)), $exclude, true))
            ->values();
    }

    public static function digits(?string $phone): string
    {
        $d = preg_replace('/\D/', '', (string) $phone);
        // 60 12 345 6789 and 012 345 6789 are the same number.
        if (str_starts_with($d, '60') && strlen($d) >= 11) {
            $d = '0' . substr($d, 2);
        }
        return $d;
    }

    private static function compact(string $s): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $s));
    }

    /**
     * Find the booking the request is for: property + unit number + check-out
     * date. Returns [booking|null, match, note].
     */
    /** Listings of a property whose name or eZee room code ends with the typed unit. */
    public static function unitsFor(string $prefix, string $unit): \Illuminate\Support\Collection
    {
        $unitKey = self::compact($unit);
        if ($unitKey === '') {
            return collect();
        }
        $inProperty = Listing::withoutGlobalScope('notArchived')->where('name', 'like', $prefix . '%')->get(['id', 'name']);
        $listings = $inProperty->filter(fn ($l) => str_ends_with(self::compact($l->name), $unitKey));
        if ($listings->isEmpty()) {
            $mapped = \Illuminate\Support\Facades\DB::table('ezee_room_mappings')
                ->whereNull('archived_at')->whereNotNull('listing_id')
                ->get(['room_name', 'listing_id'])
                ->filter(fn ($m) => self::compact($m->room_name) === $unitKey || str_ends_with(self::compact($m->room_name), $unitKey))
                ->pluck('listing_id')->unique();
            $listings = $inProperty->whereIn('id', $mapped->all());
        }
        return $listings->values();
    }

    /**
     * Likely bookings for a request Finance has to verify, each with the reason
     * it is offered: same unit within 3 days, a similar guest name at that
     * property within a week, or the same phone number within two weeks.
     *
     * @return array<int, array{booking: Booking, why: string}>
     */
    public static function suggestions(\App\Models\RefundRequest $r, int $limit = 5): array
    {
        $prefix = self::PROPERTIES[$r->property] ?? null;
        $day    = Carbon::parse($r->check_out);
        $out    = [];
        $add = function ($bookings, string $why) use (&$out, $limit) {
            foreach ($bookings as $b) {
                if (count($out) >= $limit) {
                    return;
                }
                if (!isset($out[$b->id])) {
                    $out[$b->id] = ['booking' => $b, 'why' => $why];
                }
            }
        };
        $live = fn () => Booking::where('status', '>=', 5)->with(['user:id,name,last_name,phone', 'listing:id,name']);
        $propertyIds = $prefix ? Listing::withoutGlobalScope('notArchived')->where('name', 'like', $prefix . '%')->pluck('id') : collect();

        if ($prefix) {
            $units = self::unitsFor($prefix, $r->unit_no)->pluck('id');
            if ($units->isNotEmpty()) {
                $add($live()->whereIn('listing_id', $units)
                    ->whereBetween('check_out', [$day->copy()->subDays(3)->toDateString(), $day->copy()->addDays(3)->toDateString()])
                    ->get()->sortBy(fn ($b) => abs(Carbon::parse($b->check_out)->diffInDays($day)))->take($limit), 'same unit, check-out within 3 days');
            }
        }

        $words = array_values(array_filter(preg_split('/[^a-z]+/', strtolower($r->name)), fn ($w) => strlen($w) >= 3 && !in_array($w, ['bin', 'binti', 'the', 'mr', 'mrs', 'ms'], true)));
        if ($words && $propertyIds->isNotEmpty()) {
            $q = $live()->whereIn('listing_id', $propertyIds)
                ->whereBetween('check_out', [$day->copy()->subDays(7)->toDateString(), $day->copy()->addDays(7)->toDateString()])
                ->whereHas('user', function ($u) use ($words) {
                    $u->where(function ($w) use ($words) {
                        foreach ($words as $word) {
                            $w->orWhere('name', 'like', '%' . $word . '%')->orWhere('last_name', 'like', '%' . $word . '%');
                        }
                    });
                })->get()->sortBy(fn ($b) => abs(Carbon::parse($b->check_out)->diffInDays($day)))->take($limit);
            $add($q, 'similar guest name at ' . $prefix . ', check-out within 7 days');
        }

        if ($r->phone_digits) {
            $tail = substr($r->phone_digits, -8);
            $users = User::where('phone', 'like', '%' . $tail . '%')->orWhere('phone_e164', 'like', '%' . $tail . '%')->pluck('id');
            if ($users->isNotEmpty()) {
                $add($live()->whereIn('user_id', $users)
                    ->whereBetween('check_out', [$day->copy()->subDays(14)->toDateString(), $day->copy()->addDays(14)->toDateString()])
                    ->get()->sortBy(fn ($b) => abs(Carbon::parse($b->check_out)->diffInDays($day)))->take($limit), 'same phone number, check-out within 14 days');
            }
        }

        return array_values($out);
    }

    /**
     * Resolve what Finance typed in the Link box: a booking number, an eZee
     * folio ("FN4525", repeats across hotels so the request's property and
     * date decide) or a reservation ("RES4465", "RES31670-1").
     */
    public static function resolveReference(string $ref, ?\App\Models\RefundRequest $r = null): ?Booking
    {
        $ref = strtoupper(trim($ref));
        if ($ref === '') {
            return null;
        }
        if (ctype_digit($ref)) {
            return Booking::find((int) $ref);
        }
        $candidates = collect();
        if (preg_match('/^FN\s*(\d+)$/', $ref, $m)) {
            $fn = 'FN' . $m[1];
            $candidates = Booking::where('status', '>=', 3)->where(fn ($q) => $q->where('folio_no', $fn)->orWhere('server_folio_no', $fn))->with('listing:id,name')->get();
        } elseif (preg_match('/^RES\s*([\d-]+)$/', $ref, $m)) {
            $ids = \App\OtherModel\EzeeBooking::where('SubBookingId', 'RES' . $m[1])->whereNotNull('book_id')->pluck('book_id');
            $candidates = Booking::whereIn('id', $ids)->with('listing:id,name')->get();
        }
        if ($candidates->isEmpty()) {
            return null;
        }
        if ($r && ($prefix = self::PROPERTIES[$r->property] ?? null)) {
            $same = $candidates->filter(fn ($b) => str_starts_with($b->listing->name ?? '', $prefix));
            if ($same->isNotEmpty()) {
                $candidates = $same;
            }
        }
        $day = $r ? Carbon::parse($r->check_out) : now();
        return $candidates->sortBy(fn ($b) => abs(Carbon::parse($b->check_out)->diffInDays($day, false)))->first();
    }

    public static function match(string $property, string $unit, string $checkOut, string $name, ?string $phone = null): array
    {
        $prefix = self::PROPERTIES[$property] ?? null;
        if (!$prefix) {
            return [null, 'verify', 'Property not recognised'];
        }

        $unitKey = self::compact($unit);
        if ($unitKey === '') {
            return [null, 'verify', 'No unit number'];
        }

        $inProperty = Listing::withoutGlobalScope('notArchived')->where('name', 'like', $prefix . '%')->get(['id', 'name']);

        // Homemoka unit names ("Alinea 11-08" ends with "11-08") or eZee room codes
        // guests see in their messages ("AL-11-08"; Najihah, 12 Oct 2026).
        $listings = self::unitsFor($prefix, $unit);

        if ($listings->isEmpty()) {
            $elsewhere = \Illuminate\Support\Facades\DB::table('ezee_room_mappings')->join('listings', 'listings.id', '=', 'ezee_room_mappings.listing_id')
                ->whereNull('ezee_room_mappings.archived_at')->get(['room_name', 'listings.name'])
                ->first(fn ($m) => self::compact($m->room_name) === $unitKey || str_ends_with(self::compact($m->room_name), $unitKey));
            if ($elsewhere) {
                return [null, 'verify', 'Unit "' . $unit . '" is ' . $elsewhere->name . ', not at ' . $prefix];
            }
            return [null, 'verify', 'No unit "' . $unit . '" at ' . $prefix];
        }

        $day = Carbon::parse($checkOut)->toDateString();
        $ids = $listings->pluck('id');

        $find = fn (string $from, string $to) => Booking::whereIn('listing_id', $ids)
            ->where('status', '>=', 3)->where('status', '<', 9)
            ->whereBetween('check_out', [$from, $to])
            ->with('user')
            ->orderByDesc('check_out')
            ->get();

        $exact = $find($day, $day);
        if ($exact->count() === 1) {
            $b = $exact->first();
            $nameNote = self::nameNote($b, $name);
            // Matched only when the person is confirmed too: the name overlaps the booking's
            // guest name, or the phone number is the booking's. Unit + date alone is not
            // proof (16 Oct 2026: a mis-read check-out date landed on the previous guest's
            // booking and was shown as "matched"). Otherwise Finance verifies.
            if ($nameNote === null || self::samePhone($b, $phone)) {
                return [$b, 'matched', $nameNote === null ? null : $nameNote . ' (phone matches the booking)'];
            }
            return [$b, 'verify', $nameNote . '; phone differs too. Confirm this is the guest who stayed before paying.'];
        }
        if ($exact->count() > 1) {
            return [$exact->first(), 'verify', $exact->count() . ' bookings check out that day on this unit'];
        }

        $near = $find(Carbon::parse($day)->subDay()->toDateString(), Carbon::parse($day)->addDay()->toDateString());
        if ($near->count() >= 1) {
            $b = $near->first();
            return [$b, 'verify', 'Nearest booking checks out ' . Carbon::parse($b->check_out)->format('d M') . ', guest wrote ' . Carbon::parse($day)->format('d M')];
        }

        return [null, 'verify', 'No booking checking out ' . Carbon::parse($day)->format('d M Y') . ' on ' . $listings->first()->name];
    }

    /** Last eight digits of the booking guest's phone equal the request's. */
    private static function samePhone(Booking $b, ?string $phone): bool
    {
        $a = self::digits((string) $phone);
        $c = self::digits((string) ($b->user->phone ?? ''));
        return strlen($a) >= 8 && strlen($c) >= 8 && substr($a, -8) === substr($c, -8);
    }

    private static function nameNote(Booking $b, string $name): ?string
    {
        $guest = trim(($b->user->name ?? '') . ' ' . ($b->user->last_name ?? ''));
        if ($guest === '') {
            return null;
        }
        $a = array_filter(preg_split('/\s+/', strtolower($guest)));
        $c = array_filter(preg_split('/\s+/', strtolower($name)));
        return array_intersect($a, $c) ? null : 'Name differs: booking is under ' . $guest;
    }

    /** Noon (config) on the next working day after submission: Operations' deadline to record a deduction. */
    public static function opsCutoff(Carbon $submitted): Carbon
    {
        $d = $submitted->copy()->addDay();
        while (!$d->isWeekday()) {
            $d->addDay();
        }
        return $d->setTime((int) config('moka.refund_ops_cutoff_hour', 12), 0, 0);
    }

    /** Working days (Mon–Fri) after the submission day. */
    public static function dueOn(Carbon $submitted): Carbon
    {
        $d = $submitted->copy()->startOfDay();
        $left = self::WORKING_DAYS;
        while ($left > 0) {
            $d->addDay();
            if ($d->isWeekday()) {
                $left--;
            }
        }
        return $d;
    }

    /**
     * One form, one or more units: a request per unit, grouped when there are
     * several, so each reconciles to its own eZee deposit and Finance can pay
     * them together.
     *
     * @param  string[]  $units
     * @return RefundRequest[]
     */
    public static function submitMany(array $in, array $units, ?RefundLink $link, ?string $ip): array
    {
        $units = array_values(array_unique(array_filter(array_map(fn ($u) => strtoupper(trim((string) $u)), $units))));
        if (count($units) <= 1) {
            return [self::submit(['unit_no' => $units[0] ?? $in['unit_no']] + $in, $link, $ip)];
        }
        $key = Str::lower(Str::random(12));
        $out = [];
        foreach ($units as $i => $unit) {
            $out[] = self::submit(['unit_no' => $unit] + $in, $link, $ip, ['key' => $key, 'index' => $i + 1, 'size' => count($units)]);
        }
        return $out;
    }

    /**
     * Finance splits a request that names two units ("01-28 and 01-30") into a
     * second request for the other unit: same guest, same bank details, grouped.
     */
    public static function split(RefundRequest $r, string $unit): RefundRequest
    {
        $unit = strtoupper(trim($unit));
        if ($unit === '') {
            throw new \InvalidArgumentException('Give the other unit number.');
        }
        $key = $r->group_key ?: Str::lower(Str::random(12));
        $size = ($r->group_key ? RefundRequest::where('group_key', $key)->count() : 1) + 1;
        if (!$r->group_key) {
            $r->update(['group_key' => $key, 'group_index' => 1, 'group_size' => $size]);
        } else {
            RefundRequest::where('group_key', $key)->update(['group_size' => $size]);
        }
        $in = [
            'name' => $r->name, 'email' => $r->email, 'phone' => $r->phone, 'property' => $r->property, 'unit_no' => $unit,
            'check_out' => $r->check_out->toDateString(), 'bank_name' => $r->bank_name, 'bank_holder' => $r->bank_holder, 'bank_account' => $r->bank_account,
            'rating_clean' => $r->rating_clean, 'rating_service' => $r->rating_service, 'comments' => $r->comments,
            'staff_user_id' => $r->staff_user_id, 'staff_rating' => $r->staff_rating, 'staff_comment' => $r->staff_comment, 'marketing_opt_in' => $r->marketing_opt_in,
        ];
        $new = self::submit($in, $r->link, $r->ip, ['key' => $key, 'index' => $size, 'size' => $size], false);
        $new->forceFill(['created_at' => $r->created_at, 'due_on' => $r->due_on, 'ops_cutoff_at' => $r->ops_cutoff_at, 'deposit_amount' => $r->deposit_amount, 'match_note' => Str::limit(trim('Split from request #' . $r->id . '. ' . ($new->match_note ?? '')), 250)])->save();
        return $new;
    }

    /**
     * One deposit per booking: among the live (not rejected) requests on a booking,
     * the earliest is the match and the rest are duplicates of it. Re-run whenever a
     * request is rejected, reopened or re-linked, so rejecting a wrong match promotes
     * the right one to Matched by itself (Sam, 14 Oct 2026).
     */
    public static function settleClaims(?int $bookingId): void
    {
        if (!$bookingId) {
            return;
        }
        $claims = RefundRequest::where('booking_id', $bookingId)->where('status', '!=', 'rejected')->orderBy('id')->get();
        $first = $claims->first();
        foreach ($claims as $i => $c) {
            $want = $i === 0 ? ['match' => 'matched', 'duplicate_of' => null] : ['match' => 'duplicate', 'duplicate_of' => $first->id];
            if ($c->match !== $want['match'] || (int) $c->duplicate_of !== (int) $want['duplicate_of']) {
                $note = $i === 0 ? 'Now the only live claim on this booking' : 'Same booking as request #' . $first->id;
                $c->update($want + ['match_note' => Str::limit(trim(preg_replace('/;?\s*Same booking as request #\d+/', '', (string) $c->match_note) . '; ' . $note, '; '), 250)]);
            }
        }
    }

    /** Store a guest submission; returns the saved request. */
    public static function submit(array $in, ?RefundLink $link, ?string $ip, ?array $group = null, bool $applyPhoneCap = true): RefundRequest
    {
        [$booking, $match, $note] = self::match($in['property'], $in['unit_no'], $in['check_out'], $in['name'], $in['phone'] ?? null);

        $phoneDigits = self::digits($in['phone'] ?? '');
        $notes = array_filter([$note]);

        if ($booking && $match === 'matched') {
            $earlier = RefundRequest::where('booking_id', $booking->id)->orderBy('id')->first();
            if ($earlier) {
                $match = 'duplicate';
                $notes[] = 'Same booking as request #' . $earlier->id;
            }
        }

        if ($phoneDigits !== '' && $applyPhoneCap && !$group) {
            $today = RefundRequest::where('phone_digits', $phoneDigits)->where('created_at', '>=', now()->subDay())->count();
            if ($today >= self::MAX_PER_PHONE_PER_DAY) {
                $match = $match === 'matched' ? 'verify' : $match;
                $notes[] = 'Submission ' . ($today + 1) . ' from this phone number in 24 hours';
            }
        }

        $now = now();

        // The guest profile on the matched booking gets the contact details it was
        // missing (eZee rarely carries a direct email). Existing values are never
        // overwritten; the request row keeps what the guest typed either way.
        if ($booking && in_array($match, ['matched', 'duplicate'], true) && $booking->user) {
            $fill = [];
            if (blank($booking->user->email) && !empty($in['email'])) {
                $fill['email'] = $in['email'];
            }
            if (blank($booking->user->phone) && $phoneDigits !== '') {
                $fill['phone'] = $phoneDigits;
            }
            if ($fill) {
                try {
                    User::where('id', $booking->user->id)->update($fill);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return RefundRequest::create([
            'refund_link_id' => $link?->id,
            'booking_id'     => $booking?->id,
            'match'          => $match,
            'match_note'     => $notes ? Str::limit(implode('; ', $notes), 250, '…') : null,
            'duplicate_of'   => isset($earlier) ? $earlier->id : null,
            'group_key'      => $group['key'] ?? null,
            'group_index'    => $group['index'] ?? null,
            'group_size'     => $group['size'] ?? null,
            'name'           => $in['name'],
            'email'          => $in['email'] ?? null,
            'phone'          => $in['phone'] ?? null,
            'phone_digits'   => $phoneDigits ?: null,
            'property'       => $in['property'],
            'unit_no'        => strtoupper(trim($in['unit_no'])),
            'check_out'      => $in['check_out'],
            'bank_name'      => $in['bank_name'],
            'bank_holder'    => $in['bank_holder'],
            'bank_account'   => preg_replace('/\s+/', '', $in['bank_account']),
            'rating_clean'   => $in['rating_clean'] ?? null,
            'rating_service' => $in['rating_service'] ?? null,
            'comments'       => $in['comments'] ?? null,
            'marketing_opt_in' => !empty($in['marketing_opt_in']),
            'staff_user_id'  => $in['staff_user_id'] ?? null,
            'staff_rating'   => !empty($in['staff_user_id']) ? ($in['staff_rating'] ?? null) : null,
            'staff_comment'  => !empty($in['staff_user_id']) ? ($in['staff_comment'] ?? null) : null,
            'status'         => 'pending',
            'due_on'         => self::dueOn($now),
            'deposit_amount' => config('moka.refund_deposit', 200),
            'ops_cutoff_at'  => self::opsCutoff($now),
            'ip'             => $ip,
        ]);
    }
}

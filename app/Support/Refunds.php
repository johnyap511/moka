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
    public static function match(string $property, string $unit, string $checkOut, string $name): array
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

        // 1. Homemoka unit names: "Alinea 11-08" ends with the typed "11-08".
        $listings = $inProperty->filter(fn ($l) => str_ends_with(self::compact($l->name), $unitKey));

        // 2. eZee room codes, which is what guests see in their messages: "AL-11-08",
        //    "BS 03-06", "C2-07-10" (Najihah, 12 Oct 2026: two Alinea guests typed the code).
        if ($listings->isEmpty()) {
            $mapped = \Illuminate\Support\Facades\DB::table('ezee_room_mappings')
                ->whereNull('archived_at')->whereNotNull('listing_id')
                ->get(['room_name', 'listing_id'])
                ->filter(fn ($m) => self::compact($m->room_name) === $unitKey || str_ends_with(self::compact($m->room_name), $unitKey))
                ->pluck('listing_id')->unique();
            $listings = $inProperty->whereIn('id', $mapped->all());
            if ($listings->isEmpty() && $mapped->isNotEmpty()) {
                $elsewhere = Listing::withoutGlobalScope('notArchived')->whereIn('id', $mapped->all())->value('name');
                return [null, 'verify', 'Unit "' . $unit . '" is ' . $elsewhere . ', not at ' . $prefix];
            }
        }

        if ($listings->isEmpty()) {
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
            return [$b, 'matched', self::nameNote($b, $name)];
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

    /** Store a guest submission; returns the saved request. */
    public static function submit(array $in, ?RefundLink $link, ?string $ip): RefundRequest
    {
        [$booking, $match, $note] = self::match($in['property'], $in['unit_no'], $in['check_out'], $in['name']);

        $phoneDigits = self::digits($in['phone'] ?? '');
        $notes = array_filter([$note]);

        if ($booking && $match === 'matched') {
            $earlier = RefundRequest::where('booking_id', $booking->id)->orderBy('id')->first();
            if ($earlier) {
                $match = 'duplicate';
                $notes[] = 'Same booking as request #' . $earlier->id;
            }
        }

        if ($phoneDigits !== '') {
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
            'ip'             => $ip,
        ]);
    }
}

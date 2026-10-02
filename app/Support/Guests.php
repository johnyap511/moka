<?php

namespace App\Support;

use App\OtherModel\EzeeBooking;
use App\Role;
use App\User;

/**
 * Guest profiles (8 Sep 2026). eZee sends the guest's name, email, mobile and
 * country with every reservation, so a guest is one profile reused across
 * stays rather than a new user per booking with a number glued to the name.
 * Matching: email, then mobile, then the full name when neither side has any
 * contact detail. Nothing is ever merged on name alone when a phone or email
 * exists, so two different "Chee Yin Fatt"s with different numbers stay apart.
 */
class Guests
{
    public static function profileFor(EzeeBooking $eb): User
    {
        $first = self::cleanName($eb->FirstName) ?: 'EZEE Guest';
        $last  = self::cleanName($eb->LastName);
        $email = strtolower(trim((string) $eb->Email)) ?: null;
        $phone = self::normalisePhone($eb->Mobile);

        $user = null;
        if ($email && !self::isGenericEmail($email)) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->orderBy('id')->first();
        }
        $e164 = self::e164($eb->Mobile, $eb->Country);
        $dial = self::dialOf($e164) ?: self::dialCode($eb->Country) ?: '60';
        if (!$user && $phone) {
            $user = User::whereRaw("REGEXP_REPLACE(IFNULL(phone,''), '[^0-9]', '') = ?", [$phone])->orderBy('id')->first();
        }
        if (!$user && !$email && !$phone) {
            $user = User::whereRaw('LOWER(name) = ? AND LOWER(IFNULL(last_name,\'\')) = ?', [strtolower($first), strtolower($last)])
                ->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''))
                ->where(fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
                ->orderBy('id')->first();
        }

        if ($user) {
            // Keep the profile current and complete; never blank a known detail.
            $fill = [];
            if (self::hasCounter($user->name) || $user->name !== $first) $fill['name'] = $first;
            if ($last !== '' && (string) $user->last_name !== $last) $fill['last_name'] = $last;
            if (!$user->email && $email) $fill['email'] = $email;
            if (!$user->phone && $eb->Mobile) $fill['phone'] = trim((string) $eb->Mobile);
            if (!$user->country_code || preg_match('/[A-Za-z]/', (string) $user->country_code)) $fill['country_code'] = $dial;
            if (!$user->phone_e164 && $e164) $fill['phone_e164'] = $e164;
            if ($fill) User::where('id', $user->id)->update($fill);
            return $user->fresh();
        }

        $user = User::create([
            'name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $eb->Mobile ? trim((string) $eb->Mobile) : null,
            // country_code is the dialling code (never eZee's country name, which is what was stored until 2 Oct 2026); NOT NULL, default 60.
            'country_code' => $dial, 'ezee_tmp' => 1,
        ]);
        if ($e164) {
            User::where('id', $user->id)->update(['phone_e164' => $e164]);
        }
        if ($role = Role::find(2)) {
            $user->attachRole($role);
        }

        return $user;
    }

    /** "NUR56" → "NUR": the counter the old system added to keep names unique. */
    public static function cleanName($name): string
    {
        $n = trim((string) $name);
        return preg_replace('/(?<=[A-Za-z\x{4e00}-\x{9fff}])\d{1,3}$/u', '', $n) ?? $n;
    }

    public static function hasCounter($name): bool
    {
        return (bool) preg_match('/[A-Za-z\x{4e00}-\x{9fff}]\d{1,3}$/u', (string) $name);
    }

    /** Country names as eZee sends them (sometimes cut to 10 letters) → dialling code. */
    private const DIAL = [
        'malaysia' => '60', 'indonesia' => '62', 'singapore' => '65', 'australia' => '61', 'china' => '86', 'india' => '91',
        'thailand' => '66', 'philippin' => '63', 'vietnam' => '84', 'viet nam' => '84', 'brunei' => '673', 'japan' => '81',
        'south kore' => '82', 'korea' => '82', 'hong kong' => '852', 'taiwan' => '886', 'united kin' => '44', 'united sta' => '1',
        'canada' => '1', 'germany' => '49', 'france' => '33', 'spain' => '34', 'netherland' => '31', 'saudi' => '966',
        'united ara' => '971', 'banglades' => '880', 'pakistan' => '92', 'sri lanka' => '94', 'myanmar' => '95', 'cambodia' => '855',
        'new zealan' => '64', 'russia' => '7', 'italy' => '39', 'switzerlan' => '41', 'ireland' => '353', 'qatar' => '974',
        'kuwait' => '965', 'oman' => '968', 'egypt' => '20', 'turkey' => '90', 'nigeria' => '234', 'maldives' => '960',
    ];

    /** The dialling code for a country name or an already numeric code; null when unknown. */
    public static function dialCode($country): ?string
    {
        $c = strtolower(trim((string) $country));
        if ($c === '') {
            return null;
        }
        if (ctype_digit(ltrim($c, '+'))) {
            return ltrim($c, '+');
        }
        foreach (self::DIAL as $name => $code) {
            if (str_starts_with($c, $name)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * One clean international number ("+60125880263") from whatever was typed, or null
     * when it cannot be told with confidence. Nothing is guessed beyond these rules:
     * a leading + or 00 is trusted; 60… and 0… are Malaysian; a bare 1x mobile is
     * Malaysian; otherwise the guest's country gives the prefix.
     */
    public static function e164($phone, $country = null): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }
        $raw  = preg_split('/[(\/,;]/', $raw)[0];                 // "…(823959)", "a / b": the first number
        $plus = str_starts_with(ltrim($raw), '+');
        $d    = preg_replace('/\D+/', '', $raw);
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
            $plus = true;
        }
        $n = strlen($d);
        $dial = self::dialCode($country);
        if ($n < 8) {
            return null;
        }
        if ($plus) {
            $out = $d;
        } elseif (str_starts_with($d, '60') && $n >= 10 && $n <= 12) {
            $out = $d;
        } elseif (str_starts_with($d, '0') && $n >= 9 && $n <= 11 && (!$dial || $dial === '60')) {
            $out = '6' . $d;
        } elseif (str_starts_with($d, '1') && $n >= 9 && $n <= 10 && (!$dial || $dial === '60')) {
            $out = '60' . $d;
        } elseif ($dial && $dial !== '60') {
            $out = str_starts_with($d, $dial) ? $d : $dial . ltrim($d, '0');
        } elseif (self::foreignShape($d)) {
            $out = $d;          // typed with its country code but no "+", in a shape no Malaysian number has
        } else {
            return null;
        }
        $len = strlen($out);

        return ($len >= 9 && $len <= 15) ? '+' . $out : null;
    }

    /**
     * Country code + national number lengths that cannot be a Malaysian number written
     * without its 0 or 60. Deliberately excludes +1 and +7, where 11 digits starting
     * with 1 could equally be a Chinese mobile typed without 86.
     */
    private static function foreignShape(string $d): bool
    {
        $n = strlen($d);
        foreach (['65' => [8, 8], '62' => [9, 11], '86' => [11, 11], '91' => [10, 10], '82' => [9, 10], '61' => [9, 9], '44' => [10, 10],
            '66' => [9, 9], '852' => [8, 8], '63' => [10, 10], '84' => [9, 10], '886' => [9, 9], '81' => [10, 10], '971' => [9, 9], '966' => [9, 9]] as $code => [$lo, $hi]) {
            if (str_starts_with($d, $code) && $n - strlen($code) >= $lo && $n - strlen($code) <= $hi) {
                return true;
            }
        }

        return false;
    }

    /** The dialling code inside a clean number, longest match first. */
    public static function dialOf(?string $e164): ?string
    {
        if (!$e164) {
            return null;
        }
        $d = ltrim($e164, '+');
        $codes = array_unique(array_values(self::DIAL));
        usort($codes, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($codes as $c) {
            if (str_starts_with($d, $c)) {
                return $c;
            }
        }

        return null;
    }

    /** Addresses the booking sites hand out instead of the guest's own; they stop working after the stay. */
    public static function isRelayEmail(?string $email): bool
    {
        return (bool) preg_match('/@(guest\.booking\.com|agoda-messaging\.com|m\.expediapartnercentral\.com|guest\.trip\.com|guest\.ctrip\.com|trip\.com|ctrip\.com|guest\.airbnb\.com)$/i', (string) $email);
    }

    /** A site's own support mailbox on a reservation is not the guest: never match guests on it. */
    public static function isGenericEmail(?string $email): bool
    {
        return (bool) preg_match('/^(lodgingsupport|support|noreply|no-reply|reservations?|booking|info|hello)@/i', (string) $email);
    }

    /** Digits only; null when too short to identify anyone. */
    public static function normalisePhone($phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        return strlen($d) >= 8 ? $d : null;
    }

    /**
     * A shared placeholder account: no email, no phone, and many bookings hanging off it
     * (user #10 carried 3,653 by Sep 2026). A booking form must never attach to one or
     * rename it: `where('email', null)` used to match it whenever a booking was saved
     * without phone and email, and the last guest keyed became the name on every one of them.
     */
    public static function isShared($user): bool
    {
        if (!$user || !empty($user->email) || !empty($user->phone)) {
            return false;
        }
        return \App\Booking::withoutGlobalScopes()->where('user_id', $user->id)->count() > 50; // a true placeholder, not a tenant with a dozen monthly pieces
    }
}

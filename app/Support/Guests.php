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
        if ($email) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->orderBy('id')->first();
        }
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
            if (!$user->country_code && $eb->Country) $fill['country_code'] = trim((string) $eb->Country);
            if ($fill) User::where('id', $user->id)->update($fill);
            return $user->fresh();
        }

        $user = User::create([
            'name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $eb->Mobile ? trim((string) $eb->Mobile) : null,
            'country_code' => $eb->Country ? trim((string) $eb->Country) : null, 'ezee_tmp' => 1,
        ]);
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

    /** Digits only; null when too short to identify anyone. */
    public static function normalisePhone($phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        return strlen($d) >= 8 ? $d : null;
    }
}

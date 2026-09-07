<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Ground rule 17: a month is stamped once the month after it has been reported.
 * In September the team works on August, so July and everything before it are
 * locked: no job may change a locked booking. People still can, by hand.
 */
class Lock
{
    /** First day of the previous month: bookings that checked in before it are locked. */
    public static function cutoff(): Carbon
    {
        return Carbon::now()->subMonthNoOverflow()->startOfMonth();
    }

    public static function isLocked($checkIn): bool
    {
        return $checkIn !== null && Carbon::parse($checkIn)->lt(self::cutoff());
    }

    /** A stamped booking changes only by a super admin who re-enters their password. */
    public static function unlocked($user, ?string $password): bool
    {
        return $user && admin_is_super($user) && $password !== null && $password !== '' && \Illuminate\Support\Facades\Hash::check($password, $user->password);
    }

    /** Message for a refused change to a stamped booking. */
    public static function refusal(): string
    {
        return 'This booking is in a stamped month (before ' . self::cutoff()->format('d M Y') . '). Only a super admin can change it, with their password.';
    }
}

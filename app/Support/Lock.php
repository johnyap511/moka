<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Ground rule 17: a month is stamped once the month after it has been reported.
 * In September the team works on August, so July and everything before it are
 * locked: no job may change a locked booking. People still can, by hand.
 * Ground rule 27 moves the line forward: the last month reported to owners
 * (config moka.reported_through) is locked as soon as its report is sent.
 */
class Lock
{
    /** First day of the previous month: bookings that checked in before it are locked. */
    public static function cutoff(): Carbon
    {
        $cutoff = Carbon::now()->subMonthNoOverflow()->startOfMonth();

        // Ground rule 27: once a month's report has gone to owners the month is
        // frozen straight away, without waiting for the calendar to catch up.
        $reported = config('moka.reported_through');
        if (is_string($reported) && preg_match('/^\d{4}-\d{2}$/', $reported)) {
            $after = Carbon::createFromFormat('Y-m-d', $reported . '-01')->startOfDay()->addMonthNoOverflow();
            if ($after->gt($cutoff)) {
                $cutoff = $after;
            }
        }

        return $cutoff;
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

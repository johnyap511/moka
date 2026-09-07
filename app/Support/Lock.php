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
}

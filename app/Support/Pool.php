<?php

namespace App\Support;

use App\Booking;
use App\Listing;
use App\ListingGroup;
use Carbon\Carbon;

/**
 * Pool profit sharing (ground rule 23, confirmed 8 Sep 2026). A unit of type
 * "group" belongs to a pool (a Group of listings). The pool's month is the sum
 * over every unit of room revenue plus cleaning fee, excluding SST, with the
 * M&A fee not deducted. Each owner's share is their unit's weight over the
 * pool's total weight; weight follows unit size.
 */
class Pool
{
    /** @return array{group:object,listing_ids:int[],units:int,weight:float,total_weight:float,share:float}|null */
    public static function for(?Listing $listing): ?array
    {
        if (!$listing || $listing->type !== 'group') {
            return null;
        }
        $membership = ListingGroup::where('listing_id', $listing->id)->first();
        if (!$membership || !$membership->group_id) {
            return null;
        }
        $group   = \App\Group::find($membership->group_id);
        $ids     = ListingGroup::where('group_id', $membership->group_id)->pluck('listing_id')->all();
        $weights = Listing::withArchived()->whereIn('id', $ids)->pluck('pool_weight', 'id');
        $total   = (float) $weights->sum();
        $mine    = (float) ($weights[$listing->id] ?? 1);

        return [
            'group'        => $group,
            'listing_ids'  => $ids,
            'units'        => count($ids),
            'weight'       => $mine,
            'total_weight' => $total,
            'share'        => $total > 0 ? $mine / $total : 0.0,
        ];
    }

    /**
     * The pool's figures for one calendar month: nights in the month at the
     * stamped rate, cleaning on the arrival month, bookings touching the month.
     * @return array{bookings:\Illuminate\Support\Collection,nights:int,room:float,cleaning:float,base:float}
     */
    public static function month(array $listingIds, string $monthStart, string $monthEndEx): array
    {
        $books = Booking::whereIn('listing_id', $listingIds)
            ->where('status', '>=', 5)
            ->where('check_out', '>', $monthStart)
            ->where('check_in', '<', $monthEndEx)
            ->get();

        $nights = 0; $room = 0.0; $cleaning = 0.0;
        foreach ($books as $b) {
            $cin  = max($b->check_in, $monthStart);
            $cout = min($b->check_out, $monthEndEx);
            $n    = max(0, (int) Carbon::parse($cin)->diffInDays(Carbon::parse($cout)));
            $nights += $n;
            $room   += (float) ($b->price_night ?? 0) * $n;
            if ($b->check_in >= $monthStart && $b->check_in < $monthEndEx) {
                $cleaning += (float) ($b->cleaning_fee ?? 0);
            }
        }

        return ['bookings' => $books, 'nights' => $nights, 'room' => round($room, 2), 'cleaning' => round($cleaning, 2), 'base' => round($room + $cleaning, 2)];
    }
}

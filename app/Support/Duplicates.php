<?php

namespace App\Support;

use App\Booking;
use Illuminate\Support\Facades\DB;

/**
 * A duplicate is the same stay keyed twice: same hotel, overlapping dates, and
 * the same folio number or the same guest name. Folio numbers repeat across
 * hotels, so the hotel (first word of the unit name) is always part of the test.
 * Guest name is not used: a guest or company often books several rooms at once.
 * Pieces of one stay never overlap, so they never match here.
 */
class Duplicates
{
    /** The live booking this one would duplicate, or null. */
    public static function find(int $listingId, string $checkIn, string $checkOut, ?string $folio, ?string $guest, ?int $exceptId = null): ?object
    {
        // Guest name is not a test: one guest often books several rooms. Folio only.
        $unit = DB::table('listings')->where('id', $listingId)->value('name');
        if (!$unit) {
            return null;
        }
        $hotel = strtolower(strtok($unit, ' '));
        $folio = trim((string) $folio);
        $guest = strtolower(trim(preg_replace('/\s+/', ' ', (string) $guest)));

        $q = DB::table('bookings as b')->join('listings as l', 'l.id', '=', 'b.listing_id')->leftJoin('users as u', 'u.id', '=', 'b.user_id')
            ->where('b.status', 5)->where('b.check_in', '<', $checkOut)->where('b.check_out', '>', $checkIn)
            ->whereRaw('LOWER(SUBSTRING_INDEX(l.name, " ", 1)) = ?', [$hotel])
            ->where(function ($w) use ($folio, $guest) {
                if ($folio !== '' && preg_match('/^FN\d+$/i', $folio)) {
                    $w->orWhere('b.folio_no', $folio)->orWhere('b.server_folio_no', $folio);
                }
            });
        if ($exceptId) {
            $q->where('b.id', '<>', $exceptId);
        }

        return $q->select('b.id', 'b.folio_no', 'b.check_in', 'b.check_out', 'l.name as unit', DB::raw('TRIM(CONCAT(IFNULL(u.name,""), " ", IFNULL(u.last_name,""))) as guest'))->first();
    }

    public static function message(object $dup): string
    {
        return sprintf('Looks like a duplicate of booking #%d: %s, %s to %s, %s, folio %s. Cancel that one first if it is wrong; a stay is recorded once.',
            $dup->id, $dup->guest ?: 'guest', $dup->check_in, $dup->check_out, $dup->unit, $dup->folio_no ?: '—');
    }
}

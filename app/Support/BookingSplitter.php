<?php

namespace App\Support;

use App\Booking;
use App\EzeeAssignmentLog;
use App\Listing;
use App\OtherModel\EzeeBooking;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Splits one booking across two units.
 *
 * EZEE reports a single room for a whole reservation — the last one the guest
 * occupied — so a mid-stay move arrives looking like the guest was in the final
 * room throughout. RES21820 ran 22-26 August across BS 01-28 and BS 07-30 and
 * came through as four nights in 07-30, which then blocked the two reservations
 * that genuinely held that unit on the 22nd and 23rd.
 *
 * The room history exists only in EZEE's calendar, so this cannot be derived.
 * It has to be entered by someone who can see it.
 */
class BookingSplitter
{
    /**
     * @param  string  $splitDate  the night the guest changed unit (Y-m-d)
     * @param  string  $moves      'before' or 'after' — which part of the stay was in the other unit
     * @param  int     $listingId  the unit that part was in
     * @return array{0:Booking,1:Booking} the earlier segment, then the later one
     */
    public function split(Booking $booking, string $splitDate, string $moves, int $listingId, ?int $userId = null): array
    {
        $checkIn  = substr((string) $booking->check_in, 0, 10);
        $checkOut = substr((string) $booking->check_out, 0, 10);

        if ($splitDate <= $checkIn || $splitDate >= $checkOut) {
            throw new InvalidArgumentException(
                "The split date must fall inside the stay — between {$checkIn} and {$checkOut}."
            );
        }

        if (!in_array($moves, ['before', 'after'], true)) {
            throw new InvalidArgumentException('Say which part of the stay moved: before or after the date.');
        }

        $listing = Listing::withoutGlobalScope('notArchived')->find($listingId);

        if (!$listing) {
            throw new InvalidArgumentException('That unit no longer exists.');
        }

        $nightsBefore = self::nights($checkIn, $splitDate);
        $nightsAfter  = self::nights($splitDate, $checkOut);

        // The segment that moves goes to the chosen unit; the other keeps the
        // booking's current one.
        [$fromDate, $toDate] = $moves === 'before' ? [$checkIn, $splitDate] : [$splitDate, $checkOut];

        if ($clash = self::occupied($listingId, $fromDate, $toDate, $booking->id)) {
            throw new InvalidArgumentException(
                "{$listing->name} already has booking #{$clash->id} over {$fromDate} to {$toDate}."
            );
        }

        return DB::transaction(function () use ($booking, $splitDate, $moves, $listingId, $listing,
            $checkIn, $checkOut, $nightsBefore, $nightsAfter, $userId) {

            $rate     = (float) $booking->price_night;
            $cleaning = (float) $booking->cleaning_fee;
            $sstCf    = (float) $booking->sst_cf;
            $discount = (float) $booking->discount_fee;
            $totalN   = max(1, (int) ($nightsBefore + $nightsAfter));

            // The cleaning fee and its tax are charged once at check-in, so they
            // stay with the first segment. The channel's fee is apportioned by
            // night, the same way the month split already divides it.
            $shape = function (int $nights, bool $isFirst) use ($booking, $rate, $cleaning, $sstCf, $discount, $totalN) {
                $room = round($rate * $nights, 2);
                $sst  = round($room * 0.08, 2);
                $cf   = $isFirst ? $cleaning : 0.00;
                $cfT  = $isFirst ? $sstCf : 0.00;
                $disc = $isFirst ? $discount : 0.00;

                return [
                    'nights'       => $nights,
                    'price_night'  => $rate,
                    'cleaning_fee' => $cf,
                    'sst'          => $sst,
                    'sst_cf'       => $cfT,
                    'discount_fee' => $disc,
                    'ota_fee'      => round(((float) $booking->ota_fee) * $nights / $totalN, 2),
                    'price'        => round($room + $cf + $sst + $cfT - $disc, 2),
                ];
            };

            $movedNights = $moves === 'before' ? $nightsBefore : $nightsAfter;
            $keptNights  = $moves === 'before' ? $nightsAfter  : $nightsBefore;

            // The new row carries the segment that was in the other unit; the
            // original keeps the EZEE link, so the reservation stays traceable.
            $new = collect($booking->getAttributes())->except(['id', 'created_at', 'updated_at'])->all();
            $new['listing_id'] = $listingId;
            $new['check_in']   = $moves === 'before' ? $checkIn : $splitDate;
            $new['check_out']  = $moves === 'before' ? $splitDate : $checkOut;
            $new = array_merge($new, $shape($movedNights, $moves === 'before'));
            $new['remark']     = trim(($booking->remark ?? '') . " | split stay: {$new['check_in']} to {$new['check_out']} in {$listing->name}");
            $new['created_at'] = $booking->created_at;
            $new['updated_at'] = now();

            $newId = DB::table('bookings')->insertGetId($new);

            $booking->check_in  = $moves === 'before' ? $splitDate : $checkIn;
            $booking->check_out = $moves === 'before' ? $checkOut : $splitDate;
            foreach ($shape($keptNights, $moves === 'after') as $field => $value) {
                $booking->$field = $value;
            }
            $booking->remark = trim(($booking->remark ?? '') . " | split stay: {$booking->check_in} to {$booking->check_out}");
            $booking->save();

            if ($ezee = EzeeBooking::where('book_id', $booking->id)->first()) {
                EzeeAssignmentLog::create([
                    'ezee_booking_id' => $ezee->id,
                    'listing_id'      => $listingId,
                    'old_listing_id'  => $booking->listing_id,
                    'assigned_by'     => $userId,
                    'method'          => 'manual',
                    'note'            => "Split on {$splitDate}: {$movedNights} night(s) moved to {$listing->name}, "
                                       . "{$keptNights} night(s) kept. EZEE reports one room per reservation, so a "
                                       . 'mid-stay move has to be recorded here.',
                ]);
            }

            $first  = Booking::find($moves === 'before' ? $newId : $booking->id);
            $second = Booking::find($moves === 'before' ? $booking->id : $newId);

            return [$first, $second];
        });
    }

    private static function occupied(int $listingId, string $from, string $to, int $ignoreId): ?Booking
    {
        return Booking::where('listing_id', $listingId)
            ->where('id', '!=', $ignoreId)
            ->where('status', '!=', 1)
            ->where('check_in', '<', $to)
            ->where('check_out', '>', $from)
            ->first();
    }

    private static function nights(string $from, string $to): int
    {
        return (int) ((strtotime($to) - strtotime($from)) / 86400);
    }
}

<?php

namespace App\Support;

use DateTime;

/**
 * Derives the price breakdown for an EZEE booking that has not been assigned
 * to a listing yet.
 *
 * Once a booking is assigned, the figures live on the Booking record. Before
 * that they have to be recomputed from the raw EZEE payload, which is what
 * this class does — mirroring the maths in BookController::ezeeBookingStoreEdit
 * so the preview matches what will actually be stored on assignment.
 */
class EzeePricing
{
    private const CHECK_DATE      = '2022-11-30';
    private const CHECK_DATE_15   = '2023-02-01';
    private const CHECK_DATE_NEW  = '2023-06-17';
    private const CHECK_DATE_NEW8 = '2023-07-01';
    private const SEP_DATE        = '2024-09-01';
    private const SST_DATE        = '2024-03-01';

    private const RATES = [
        'DEFAULT'    => 0.20,
        'BOOKING_1'  => 0.18,
        'BOOKING_2'  => 0.028,
        'AIRBNB'     => 0.159,
        'AIRBNB_SEP' => 0.15,
        'TRAVELOKA'  => 0.17,
        'WALK_IN'    => 0.12,
        'WALK_IN8'   => 0.08,
        'EXPEDIA'    => 0.15,
        'CTRIP'      => 0.15,
    ];

    /**
     * @return array{price_night:float,sst:float,cleaning_fee:float,sst_cf:float,ota_fee:float,total:float,nights:int}
     */
    public static function breakdown($ezee): array
    {
        $nights = self::nights($ezee->Start, $ezee->End);

        $priceNight  = $nights > 0 ? ($ezee->TotalAmountBeforeTax / $nights) : 0.0;
        $roomTotal   = $priceNight * $nights;
        $cleaningFee = (float) ($ezee->TotalExtraCharge ?? 0);
        $discount    = (float) ($ezee->TotalDiscount ?? 0);

        $bookedOn = $ezee->created_at ? $ezee->created_at->format('Y-m-d') : date('Y-m-d');
        $sstRate  = $bookedOn < self::SST_DATE ? 0.06 : 0.08;

        $sst   = self::floor2($roomTotal * $sstRate);
        $sstCf = self::floor2($cleaningFee * $sstRate);

        $otaFee = self::otaFee(
            self::normaliseSource($ezee->Source),
            new DateTime($bookedOn),
            $roomTotal,
            $cleaningFee,
            $sst,
            $sstCf
        );

        return [
            'nights'       => $nights,
            'price_night'  => $priceNight,
            'sst'          => $sst,
            'cleaning_fee' => $cleaningFee,
            'sst_cf'       => $sstCf,
            'ota_fee'      => $otaFee,
            'total'        => $roomTotal + $cleaningFee + $sst + $sstCf - $discount,
        ];
    }

    private static function otaFee(string $source, DateTime $bookedOn, float $roomTotal, float $cleaningFee, float $sst, float $sstCf): float
    {
        $base      = $roomTotal + $cleaningFee;   // ota_cal / ota_cal2
        $baseTaxed = $base + $sst + $sstCf;       // ota_cal1

        $afterCheck  = $bookedOn > new DateTime(self::CHECK_DATE);
        $afterNew    = $bookedOn > new DateTime(self::CHECK_DATE_NEW);
        $beforeNew   = $bookedOn < new DateTime(self::CHECK_DATE_NEW);

        if (in_array($source, ['Walk-in', 'Walk In', 'PMS', 'Website'], true)) {
            if ($bookedOn >= new DateTime(self::CHECK_DATE_NEW8)) {
                return self::floor2(self::RATES['WALK_IN8'] * $base);
            }
            if ($bookedOn > new DateTime(self::CHECK_DATE_15)) {
                return self::floor2(self::RATES['WALK_IN'] * $base);
            }
            if ($afterCheck) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            return self::floor2(0.20 * $base);
        }

        if ($source === 'Airbnb') {
            if ($bookedOn >= new DateTime(self::SEP_DATE)) {
                return self::floor2(self::RATES['AIRBNB_SEP'] * $baseTaxed);
            }
            if ($afterCheck && $beforeNew) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            return self::floor2(self::RATES['AIRBNB'] * $base);
        }

        if (in_array($source, ['Booking.com', 'Booking'], true)) {
            if ($afterCheck && $beforeNew) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            if ($afterNew) {
                return self::floor2(
                    self::floor2(self::RATES['BOOKING_2'] * $baseTaxed)
                    + self::floor2(self::RATES['BOOKING_1'] * $base)
                );
            }
            return self::floor2(0.205 * $base);
        }

        if ($source === 'Traveloka') {
            if ($afterCheck && $beforeNew) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            if ($afterNew) {
                return self::floor2(self::RATES['TRAVELOKA'] * $baseTaxed);
            }
            return self::floor2(0.18 * $base);
        }

        if (in_array($source, ['Trip.com', 'CTrip.com', 'Ctrip.com', 'CTrip', 'Ctrip'], true)) {
            if ($afterCheck && $beforeNew) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            if ($afterNew) {
                return 0.0;
            }
            return self::floor2(self::RATES['CTRIP'] * $base);
        }

        if ($source === 'Expedia') {
            if ($afterNew) {
                return self::floor2(self::RATES['DEFAULT'] * $baseTaxed);
            }
            if ($afterCheck) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            return self::floor2(self::RATES['EXPEDIA'] * $base);
        }

        // Sources that never carry a marketing & administration fee.
        if (in_array($source, ['Agoda', 'Long Term Rental', 'Tiket.com', 'owner', 'Owner'], true)) {
            return 0.0;
        }

        if ($afterCheck && $beforeNew) {
            return self::floor2(self::RATES['DEFAULT'] * $base);
        }
        if ($afterNew) {
            return self::floor2(self::RATES['DEFAULT'] * $baseTaxed);
        }
        return self::floor2(0.1 * $base);
    }

    /**
     * EZEE appends booking references to some source names
     * ("Booking.com-13707539"), so strip anything that is not part of the
     * channel name before matching against the rate table.
     */
    private static function normaliseSource($source): string
    {
        return trim(preg_replace('/[^A-Za-z\. ]/', '', (string) $source));
    }

    private static function nights($start, $end): int
    {
        if (empty($start) || empty($end)) {
            return 0;
        }
        $from = date_create($start);
        $to   = date_create($end);
        if (!$from || !$to || $to <= $from) {
            return 0;
        }
        return (int) date_diff($from, $to)->format('%a');
    }

    private static function floor2(float $value): float
    {
        return floor($value * 100) / 100;
    }
}

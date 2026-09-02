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
    private const SST_DATE        = '2024-03-01';

    /**
     * Everything below this date is the historical record of what was actually
     * charged and must never change. Corrections apply from here forward only.
     */
    private const CUTOVER_V6      = '2026-09-01';

    private const RATES = [
        'DEFAULT'    => 0.20,
        'BOOKING_1'  => 0.18,
        'BOOKING_2'  => 0.028,
        'AIRBNB'     => 0.159,
        'TRAVELOKA'  => 0.17,
        'WALK_IN'    => 0.12,
        'WALK_IN8'   => 0.08,
        'EXPEDIA'    => 0.15,
        'CTRIP'      => 0.15,
    ];

    /**
     * Channels that report what they actually charged, in TACommision. From the
     * cutover that figure is the fee — it ties to Airbnb's and Expedia's own
     * remittance statements to the cent, and no rate table can reproduce
     * Expedia, whose accelerator and promotions move every booking.
     *
     * Airbnb's figure is inclusive of VAT (invoice reads "Host service fee
     * (15.5% + VAT)" = 117.18, which is what EZEE reports). Expedia's is
     * inclusive of commission, tax on commission and the accelerator.
     *
     * Booking.com is deliberately absent. Its reported figure spikes at exactly
     * 18.00% of base across 3,626 rows — a bare commission, with neither SST
     * nor the 2.8% payment fee in it — so adopting it would drop both and
     * leave us absorbing the payment fee. The v6 formula below was verified
     * against a bank payout, so it stays until a Booking.com remittance
     * statement says otherwise.
     */
    private const REPORTS_COMMISSION = ['Airbnb', 'Expedia', 'Traveloka'];

    /**
     * Channels that remit net: their commission is already out of the figures
     * EZEE sends us, so charging it again would bill the owner twice.
     */
    /**
     * Airbnb host service fee rates, as a percentage of the gross base and
     * inclusive of SST at the rate of the day (15.5%/15%/3% x 1.08 or x 1.06).
     * Used only to tell whether EZEE sent us a gross or a net room rate.
     */
    private const AIRBNB_RATES = [16.74, 16.43, 16.2, 15.9, 3.24, 3.18];

    private const NET_REMITTANCE = [
        'Agoda', 'Tiket.com', 'Trip.com', 'CTrip.com', 'Ctrip.com', 'CTrip', 'Ctrip',
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
            $sstCf,
            isset($ezee->TACommision) ? (float) $ezee->TACommision : null
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

    /**
     * Marketing & administration fee for a single booking.
     *
     * Exposed so the assignment and reporting paths compute the fee exactly as
     * the EZEE list previews it. Each used to carry its own copy of the rate
     * table, and they had drifted apart — Airbnb was charged at three different
     * rates depending on which screen you looked at.
     *
     * @param string|null $source   Channel name; a booking reference suffix is tolerated.
     * @param string|null $bookedOn Y-m-d the booking was made. Defaults to today.
     */
    public static function marketingFee($source, float $roomTotal, float $cleaningFee, float $sst, float $sstCf, ?string $bookedOn = null, ?float $actualCommission = null): float
    {
        return self::otaFee(
            self::normaliseSource($source),
            new DateTime($bookedOn ?: date('Y-m-d')),
            $roomTotal,
            $cleaningFee,
            $sst,
            $sstCf,
            $actualCommission
        );
    }

    private static function otaFee(string $source, DateTime $bookedOn, float $roomTotal, float $cleaningFee, float $sst, float $sstCf, ?float $actualCommission = null): float
    {
        $base      = $roomTotal + $cleaningFee;   // ota_cal / ota_cal2
        $baseTaxed = $base + $sst + $sstCf;       // ota_cal1

        $afterCutover = $bookedOn >= new DateTime(self::CUTOVER_V6);

        // The M&A fee is a pass-through of what the channel charged, so prefer
        // the channel's own figure over any rate table. Restricted to the
        // channels known to report it, so that re-sourcing a booking by hand
        // cannot pick up a commission belonging to a different channel.
        if ($afterCutover
            && $actualCommission !== null
            && $actualCommission > 0
            && in_array($source, self::REPORTS_COMMISSION, true)) {

            // Airbnb sends the room rate net of its host service fee on some
            // bookings and gross on others — confirmed against an Airbnb payout
            // where 540.84 + 117.18 came to the invoice's 658.02. On a net row
            // the fee has already been taken, so charging it again bills the
            // owner twice. Exactly one of the two readings lands on a real
            // Airbnb rate, and that is what decides it.
            if ($source === 'Airbnb' && self::storedNetOfFee($roomTotal + $cleaningFee, $actualCommission)) {
                return 0.0;
            }

            return self::round2($actualCommission);
        }

        if ($afterCutover && in_array($source, self::NET_REMITTANCE, true)) {
            return 0.0;
        }

        $afterCheck  = $bookedOn > new DateTime(self::CHECK_DATE);
        $afterNew    = $bookedOn > new DateTime(self::CHECK_DATE_NEW);
        $beforeNew   = $bookedOn < new DateTime(self::CHECK_DATE_NEW);

        if (in_array($source, ['Walk-in', 'Walk In', 'PMS', 'Website'], true)) {
            // From the cutover these round half-up like the rest of the 8%
            // group; otherwise Website and WEB — the same rule under two names —
            // differ by a sen.
            if ($bookedOn >= new DateTime(self::CUTOVER_V6)) {
                return self::round2(self::RATES['WALK_IN8'] * $base);
            }
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

        // Rate card: Airbnb is 15.9% excluding tax, so it applies to the
        // untaxed base. Production applies 15% to the taxed base for bookings
        // after 2024-09-01; the rate card is authoritative.
        if ($source === 'Airbnb') {
            if ($afterCheck && $beforeNew) {
                return self::floor2(self::RATES['DEFAULT'] * $base);
            }
            return self::floor2(self::RATES['AIRBNB'] * $base);
        }

        if (in_array($source, ['Booking.com', 'Booking'], true)) {
            // From the cutover: the commissionable base includes cleaning SST,
            // and Booking.com adds its own 8% on the whole fee. Both were
            // missing, which is why historical records run ~RM6 per booking
            // short. Verified against a bank payout — see spec v5 §3.
            if ($bookedOn >= new DateTime(self::CUTOVER_V6)) {
                $commissionable = $roomTotal + $cleaningFee + $sstCf;
                $commission     = self::round2(self::RATES['BOOKING_1'] * $commissionable);
                $psf            = self::round2(self::RATES['BOOKING_2'] * $baseTaxed);

                return self::round2(($commission + $psf) * 1.08);
            }

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

        // Confirmed by the business: no marketing fee. Applied from the cutover
        // so historical records keep whatever was charged at the time.
        if ($bookedOn >= new DateTime(self::CUTOVER_V6)
            && in_array($source, ['Monthly Rental', 'Ruiying'], true)) {
            return 0.0;
        }

        // These are the website rate under different names. Without this they
        // fell through to the 20% default.
        if ($bookedOn >= new DateTime(self::CUTOVER_V6)
            && in_array($source, ['Book On Google', 'WEB', 'Internet Booking Engine', 'Homemoka'], true)) {
            return self::round2(self::RATES['WALK_IN8'] * $base);
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
     * Every channel name the rate table branches on, longest first so that
     * "Booking.com" is preferred over "Booking" and "Trip.com" over "Trip".
     */
    private const KNOWN_SOURCES = [
        'Long Term Rental', 'Booking.com', 'CTrip.com', 'Ctrip.com', 'Trip.com',
        'Tiket.com', 'Traveloka', 'Expedia', 'Website', 'Walk-in', 'Walk In',
        'Booking', 'Airbnb', 'Agoda', 'Ctrip', 'CTrip', 'Owner', 'owner', 'PMS',
        'Internet Booking Engine', 'Book On Google', 'Monthly Rental', 'Ruiying',
        'Homemoka', 'WEB',
    ];

    /**
     * EZEE appends a booking reference to some source names
     * ("Booking.com-13707539", "Traveloka-SEiOXzcRUDcF"). Match on the leading
     * channel name so the reference cannot push a booking onto the default
     * rate — matching on the whole string silently cost Traveloka bookings the
     * 17% rate and charged them 20% instead.
     *
     * Prefix matching rather than splitting on the hyphen, because "Walk-in"
     * contains one legitimately.
     */
    private static function normaliseSource($source): string
    {
        $raw = trim((string) $source);

        foreach (self::KNOWN_SOURCES as $known) {
            if (stripos($raw, $known) === 0) {
                return $known;
            }
        }

        return trim(preg_replace('/[^A-Za-z\. ]/', '', $raw));
    }

    /**
     * True when the commission reconstructs a real Airbnb rate only if the
     * stored base is read as already net of it.
     */
    private static function storedNetOfFee(float $base, float $commission): bool
    {
        if ($base <= 0 || $commission <= 0) {
            return false;
        }

        $asGross = $commission / $base * 100;
        $asNet   = $commission / ($base + $commission) * 100;

        $grossFits = $netFits = false;
        foreach (self::AIRBNB_RATES as $rate) {
            if (abs($asGross - $rate) < 0.15) {
                $grossFits = true;
            }
            if (abs($asNet - $rate) < 0.15) {
                $netFits = true;
            }
        }

        // Ambiguous or unrecognised rates fall through to the gross reading,
        // which is the business's stated default.
        return $netFits && !$grossFits;
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

    /**
     * ROUND_HALF_UP, per spec v5 §8. floor2 remains for the historical branches:
     * changing how an old booking rounds would rewrite the record.
     */
    private static function round2(float $value): float
    {
        return round($value, 2);
    }
}

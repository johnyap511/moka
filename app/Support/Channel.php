<?php

namespace App\Support;

/**
 * The one channel list the whole platform uses (docs/GROUND-RULES.md, rule 12).
 * Sync, both exports, the owner portal and the fee table all go through here,
 * so a channel cannot be spelled or charged differently in two places.
 */
class Channel
{
    public const BOOKING   = 'Booking.com';
    public const AGODA     = 'Agoda';
    public const EXPEDIA   = 'Expedia';
    public const AIRBNB    = 'Airbnb';
    public const TRIP      = 'Trip.com';
    public const TRAVELOKA = 'Traveloka';
    public const TIKET     = 'Tiket.com';
    public const WEBSITE   = 'Website';
    public const LTR       = 'Long Term Rental';
    public const OWNER     = 'Owner';

    /** Direct business: Walk In, PMS, Google, Internet Booking Engine, Monthly Rental. Shown as Website, 8% M&A fee. */
    private const DIRECT = ['walk', 'pms', 'google', 'book on google', 'internet', 'booking engine', 'website', 'web', 'homemoka', 'monthly'];

    /** Map any raw source (eZee's, or one typed by staff) to the canonical name. */
    public static function canonical(?string $source): string
    {
        $s = trim((string) $source);
        $s = trim(preg_replace('/[-_ ]?[A-Za-z]*\d+.*$/', '', $s)) ?: $s; // "Booking.com-12207081", "TravelokaLmtqOwCIghdh" keeps the name only
        $l = strtolower($s);

        return match (true) {
            $l === '' => '',
            str_starts_with($l, 'booking') => self::BOOKING,
            str_starts_with($l, 'agoda') => self::AGODA,
            str_starts_with($l, 'expedia') => self::EXPEDIA,
            str_starts_with($l, 'airbnb') => self::AIRBNB,
            str_starts_with($l, 'ctrip') || str_starts_with($l, 'trip.com') || str_starts_with($l, 'trip') => self::TRIP,
            str_starts_with($l, 'traveloka') => self::TRAVELOKA,
            str_starts_with($l, 'tiket') => self::TIKET,
            str_starts_with($l, 'long term') => self::LTR,
            str_starts_with($l, 'owner') => self::OWNER,
            self::startsWithAny($l, self::DIRECT) => self::WEBSITE,
            default => $s,
        };
    }

    /** Direct channel: the 8% M&A fee on the room charge before tax (rule 9). */
    public static function isDirect(?string $source): bool
    {
        return self::canonical($source) === self::WEBSITE;
    }

    /** Channels that never carry a fee: net-rate OTAs, tenancies, the owner's own stays (rule 9). */
    public static function isFeeFree(?string $source): bool
    {
        return in_array(self::canonical($source), [self::AGODA, self::TRIP, self::TIKET, self::LTR, self::OWNER], true);
    }

    private static function startsWithAny(string $l, array $prefixes): bool
    {
        foreach ($prefixes as $p) {
            if (str_starts_with($l, $p)) {
                return true;
            }
        }

        return false;
    }
}

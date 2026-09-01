<?php

namespace App\Support;

use App\EzeeRoomMapping;
use App\Listing;
use Illuminate\Support\Collection;

/**
 * Resolves an EZEE unit to the listing that represents it.
 *
 * EZEE identifies the unit on every booking as eZeePMSRoomid (e.g. "AL-11-08"),
 * stored locally as EzeeBooking.RoomName.
 *
 * Two sources of that pairing exist for historical reasons:
 *
 *   ezee_room_mappings   written by the room mapping screen — what the team maintains
 *   listings.ezee_room_id  set by hand on individual listings
 *
 * Auto-assignment previously read only the second, which no listing had set, so
 * the mappings the team entered on the screen were never used. The screen is
 * authoritative here; the listing column is kept as a lower-precedence fallback
 * so anything already relying on it keeps working.
 */
class EzeeUnitMap
{
    /**
     * @return Collection<string,Listing> keyed by normalised unit name
     */
    public static function build(): Collection
    {
        $listings = Listing::all()->keyBy('id');
        $map      = [];

        foreach ($listings as $listing) {
            if (filled($listing->ezee_room_id)) {
                $map[self::key($listing->ezee_room_id)] = $listing;
            }
        }

        $mappings = EzeeRoomMapping::whereNotNull('listing_id')
            ->whereNotNull('room_name')
            ->where('room_name', '!=', '')
            ->get();

        foreach ($mappings as $mapping) {
            if ($listing = $listings->get($mapping->listing_id)) {
                $map[self::key($mapping->room_name)] = $listing;
            }
        }

        return collect($map);
    }

    /** Unit names are compared case- and whitespace-insensitively. */
    public static function key(?string $roomName): string
    {
        return strtolower(trim((string) $roomName));
    }
}

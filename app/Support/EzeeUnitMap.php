<?php

namespace App\Support;

use App\EzeeGroup;
use App\EzeeRoom;
use App\EzeeRoomMapping;
use App\Listing;
use App\OtherModel\EzeeBooking;
use Illuminate\Support\Collection;

/**
 * Resolves an EZEE unit to the listing that represents it.
 *
 * EZEE identifies the unit on a booking as eZeePMSRoomid (e.g. "AL-11-08"),
 * stored locally as EzeeBooking.RoomName.
 *
 * A unit name is only unique *within* a property. "Extra Room 1" through
 * "Extra Room 5" each exist in four different properties, so resolving on the
 * name alone would send every one of their bookings to a single listing and put
 * guests on the wrong owner's calendar. The property therefore forms part of
 * the key wherever it is known.
 *
 * Bookings do not carry ezee_group_id — it is null on all of them — but
 * TransactionId is prefixed with the hotel code, which identifies the property.
 *
 * Mappings saved before this distinction existed have no property recorded.
 * Those still resolve by name alone, but only for names that belong to exactly
 * one property, so an ambiguous unit can never be matched by accident.
 */
class EzeeUnitMap
{
    /** @var Collection<string,Listing>|null keyed by "hotelcode|unit" */
    private ?Collection $byProperty = null;

    /** @var Collection<string,Listing>|null keyed by unit, unambiguous names only */
    private ?Collection $byNameOnly = null;

    /** @var Collection<int,string>|null hotel codes, longest first */
    private ?Collection $hotelCodes = null;

    /** @var Collection<string,Collection<int,string>>|null unit name => hotel codes owning it */
    private ?Collection $unitOwners = null;

    public static function make(): self
    {
        return new self();
    }

    /**
     * The listing for this booking's unit, or null when it is unmapped or the
     * name is ambiguous and no property-specific mapping exists.
     */
    public function resolve(EzeeBooking $booking): ?Listing
    {
        $this->load();

        $unit = self::key($booking->RoomName);

        if ($unit === '') {
            return null;
        }

        $hotelCode = $this->hotelCodeFor($booking);

        if ($hotelCode !== null) {
            $match = $this->byProperty->get($hotelCode . '|' . $unit);

            if ($match) {
                return $match;
            }

            // The booking's property is known, and EZEE says this unit belongs to
            // a different one. Falling back to the name would move a guest across
            // properties: RS-35-12 is a Forum unit, but RES6103 at Bell Suites
            // carries that name too, and matching on the name alone proposed
            // moving a Bell Suites booking into Forum.
            $owners = $this->unitOwners->get($unit);

            if ($owners && !$owners->contains($hotelCode)) {
                return null;
            }
        }

        return $this->byNameOnly->get($unit);
    }

    /** The property a booking belongs to, read from its TransactionId prefix. */
    public function hotelCodeFor(EzeeBooking $booking): ?string
    {
        $this->load();

        $transaction = (string) $booking->TransactionId;

        foreach ($this->hotelCodes as $code) {
            if ($code !== '' && str_starts_with($transaction, $code)) {
                return $code;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        $this->load();

        return $this->byProperty->isEmpty() && $this->byNameOnly->isEmpty();
    }

    /** Unit names are compared case- and whitespace-insensitively. */
    public static function key(?string $roomName): string
    {
        return strtolower(trim((string) $roomName));
    }

    private function load(): void
    {
        if ($this->byProperty !== null) {
            return;
        }

        // Archived properties are no longer managed, so a mapping pointing at
        // one resolves to nothing rather than putting a booking on the calendar
        // of an owner whose property we have handed back.
        $listings   = Listing::active()->get()->keyBy('id');
        $groupCodes = EzeeGroup::pluck('hotel_code', 'id')->map(fn ($c) => (string) $c);

        $this->hotelCodes = $groupCodes->values()
            ->filter()
            ->unique()
            ->sortByDesc(fn ($c) => strlen($c))
            ->values();

        $byProperty = [];
        $candidates = [];

        // Lowest precedence: a unit id set directly on a listing. It carries no
        // property, so it can only ever be a name-only candidate.
        foreach ($listings as $listing) {
            if (filled($listing->ezee_room_id)) {
                $candidates[self::key($listing->ezee_room_id)][] = $listing;
            }
        }

        // Archived units are ones the business no longer manages, so nothing
        // should be assigned to them. Existing bookings already assigned are
        // left alone; this only stops new ones being created.
        $mappings = EzeeRoomMapping::whereNotNull('listing_id')
            ->whereNotNull('room_name')
            ->where('room_name', '!=', '')
            ->whereNull('archived_at')
            ->get();

        foreach ($mappings as $mapping) {
            $listing = $listings->get($mapping->listing_id);

            if (!$listing) {
                continue;
            }

            $unit = self::key($mapping->room_name);
            $code = $mapping->ezee_group_id ? ($groupCodes[$mapping->ezee_group_id] ?? null) : null;

            if ($code) {
                $byProperty[$code . '|' . $unit] = $listing;
            }

            $candidates[$unit][] = $listing;
        }

        // A name-only mapping is safe only where the name means one thing. Where
        // several listings claim the same name, the property must decide.
        $byNameOnly = [];

        foreach ($candidates as $unit => $matches) {
            $distinct = collect($matches)->unique('id');

            if ($distinct->count() === 1) {
                $byNameOnly[$unit] = $distinct->first();
            }
        }

        // Which property each unit name actually belongs to, per EZEE's own
        // inventory. Used to refuse a name-only match that would cross
        // properties.
        $this->unitOwners = EzeeRoom::get(['room_name', 'hotel_code'])
            ->groupBy(fn ($room) => self::key($room->room_name))
            ->map(fn ($rooms) => $rooms->pluck('hotel_code')->map(fn ($c) => (string) $c)->unique()->values());

        $this->byProperty = collect($byProperty);
        $this->byNameOnly = collect($byNameOnly);
    }
}

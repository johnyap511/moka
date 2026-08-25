<?php

namespace App\Console\Commands;

use App\Listing;
use App\OtherModel\EzeeBooking;
use Illuminate\Console\Command;

/**
 * Match EZEE unit ids against listing names so ezee_room_id can be filled in
 * without visiting 300 listings by hand.
 *
 * EZEE unit ids look like "C2-07-10" and listings are named after the same
 * units ("Ekocheras J-28-09"), so a containment match resolves most of them.
 * Only unambiguous matches are applied — anything matching several listings, or
 * none, is reported for a human to decide.
 */
class SuggestEzeeRoomIds extends Command
{
    protected $signature = 'ezee:suggest-room-ids
                            {--apply : write the unambiguous matches}
                            {--limit=0 : only show this many rows per section}';

    protected $description = 'Suggest ezee_room_id values for listings from EZEE unit ids';

    public function handle()
    {
        $roomIds = EzeeBooking::query()
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->distinct()
            ->orderBy('RoomName')
            ->pluck('RoomName');

        if ($roomIds->isEmpty()) {
            $this->error('No EZEE unit ids recorded yet. Run ezee:backfill-room-ids first.');
            return 1;
        }

        $listings = Listing::get(['id', 'name', 'ezee_room_id']);
        $apply    = (bool) $this->option('apply');
        $limit    = (int) $this->option('limit');

        $exact = [];
        $ambiguous = [];
        $unmatched = [];
        $already = 0;

        foreach ($roomIds as $roomId) {
            $needle = strtolower(trim($roomId));

            if ($listings->first(fn ($l) => strtolower(trim((string) $l->ezee_room_id)) === $needle)) {
                $already++;
                continue;
            }

            $hits = $listings->filter(function ($l) use ($needle) {
                return str_contains(strtolower((string) $l->name), $needle);
            })->values();

            if ($hits->count() === 1) {
                $exact[] = [$roomId, $hits[0]->id, $hits[0]->name];
            } elseif ($hits->count() > 1) {
                $ambiguous[] = [$roomId, $hits->pluck('name')->implode(' | ')];
            } else {
                $unmatched[] = [$roomId];
            }
        }

        $this->info(sprintf(
            '%d unit id(s): %d already set, %d matched, %d ambiguous, %d unmatched',
            $roomIds->count(), $already, count($exact), count($ambiguous), count($unmatched)
        ));

        if ($exact) {
            $this->newLine();
            $this->info($apply ? 'Applying:' : 'Would apply (re-run with --apply):');
            $this->table(['EZEE unit', 'listing id', 'listing name'],
                $limit ? array_slice($exact, 0, $limit) : $exact);

            if ($apply) {
                foreach ($exact as [$roomId, $listingId, $_]) {
                    Listing::where('id', $listingId)->update(['ezee_room_id' => $roomId]);
                }
                $this->info(count($exact) . ' listing(s) updated.');
            }
        }

        if ($ambiguous) {
            $this->newLine();
            $this->warn('Ambiguous — more than one listing matched, set these by hand:');
            $this->table(['EZEE unit', 'candidates'],
                $limit ? array_slice($ambiguous, 0, $limit) : $ambiguous);
        }

        if ($unmatched) {
            $this->newLine();
            $this->warn('No listing matched — these units may need a listing creating:');
            $this->table(['EZEE unit'],
                $limit ? array_slice($unmatched, 0, $limit) : $unmatched);
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Booking;
use App\EzeeGroup;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Support\EzeeBookingFeed;
use Illuminate\Console\Command;

/**
 * Work out which listing each EZEE unit belongs to, using bookings that have
 * already been assigned.
 *
 * Every assigned booking is a decision someone made: this EZEE reservation
 * belongs to that listing. Fetching those same reservations' unit ids from EZEE
 * turns those decisions into a unit -> listing map that is grounded in real
 * data, rather than guessed from how closely a unit id resembles a listing name.
 *
 * Read-only against bookings. Only listings.ezee_room_id is written, and only
 * with --apply.
 */
class DeriveEzeeRoomMapping extends Command
{
    protected $signature = 'ezee:derive-room-mapping
                            {--months=3 : how far back to look}
                            {--min-confidence=80 : percent of bookings that must agree}
                            {--apply : write ezee_room_id onto the listings}';

    protected $description = 'Derive listing/EZEE-unit mappings from already-assigned bookings';

    public function handle()
    {
        set_time_limit(0);

        $months = max(1, (int) $this->option('months'));
        $minPct = max(1, min(100, (int) $this->option('min-confidence')));
        $apply  = (bool) $this->option('apply');

        $from = date('Y-m-d', strtotime("-{$months} months"));
        $to   = date('Y-m-d');

        $this->info("Looking at assigned bookings from {$from} to {$to}");

        // Reservation -> listing, straight from what was already assigned.
        $assigned = EzeeBooking::query()
            ->whereNotNull('book_id')->where('book_id', '>', 0)
            ->whereBetween('Start', [$from, $to])
            ->get(['id', 'SubBookingId', 'TransactionId', 'book_id']);

        if ($assigned->isEmpty()) {
            $this->error('No assigned bookings in that window — try a longer --months.');
            return 1;
        }

        $listingByBooking = Booking::whereIn('id', $assigned->pluck('book_id'))
            ->pluck('listing_id', 'id');

        $this->info("{$assigned->count()} assigned booking(s) to learn from.");

        $feed    = new EzeeBookingFeed(fn ($line) => $this->line($line));
        $byHotel = $assigned->groupBy(fn ($b) => substr((string) $b->TransactionId, 0, 5));

        // unit id => [listing_id => times seen]
        $votes = [];

        foreach (EzeeGroup::all() as $group) {
            $rows = $byHotel[(string) $group->hotel_code] ?? collect();
            if ($rows->isEmpty()) {
                continue;
            }

            $this->line("  {$group->hotel_code} {$group->name}: {$rows->count()} assigned");
            $roomIds = $feed->roomIds($group, $from, $to);

            foreach ($rows as $row) {
                $roomId    = $roomIds[$row->SubBookingId] ?? null;
                $listingId = $listingByBooking[$row->book_id] ?? null;

                if ($roomId === null || $listingId === null) {
                    continue;
                }

                $votes[$roomId][$listingId] = ($votes[$roomId][$listingId] ?? 0) + 1;
            }
        }

        if (!$votes) {
            $this->error('No unit ids came back — check the auth codes.');
            return 1;
        }

        $listings = Listing::whereIn('id', collect($votes)->flatMap(fn ($v) => array_keys($v))->unique())
            ->get(['id', 'name', 'ezee_room_id'])
            ->keyBy('id');

        $confident = [];
        $mixed     = [];

        foreach ($votes as $roomId => $tally) {
            arsort($tally);
            $total    = array_sum($tally);
            $topId    = array_key_first($tally);
            $topCount = $tally[$topId];
            $pct      = (int) round($topCount / $total * 100);
            $listing  = $listings[$topId] ?? null;

            if (!$listing) {
                continue;
            }

            $row = [$roomId, $listing->name, "{$topCount}/{$total}", "{$pct}%"];

            if ($pct >= $minPct) {
                $confident[] = [$roomId, $listing->id, $listing->name, $row[2], $row[3]];
            } else {
                $names = [];
                foreach ($tally as $lid => $n) {
                    $names[] = ($listings[$lid]->name ?? "#{$lid}") . " ({$n})";
                }
                $mixed[] = [$roomId, implode(', ', array_slice($names, 0, 4))];
            }
        }

        $this->newLine();
        $this->info(sprintf('%d unit(s) seen: %d confident, %d mixed',
            count($votes), count($confident), count($mixed)));

        if ($confident) {
            $this->newLine();
            $this->info($apply ? 'Applying:' : 'Proposed mapping (re-run with --apply to write):');
            $this->table(
                ['EZEE unit', 'listing id', 'listing', 'agreement', 'confidence'],
                $confident
            );

            if ($apply) {
                $written = 0;
                foreach ($confident as [$roomId, $listingId, , , ]) {
                    Listing::where('id', $listingId)->update(['ezee_room_id' => $roomId]);
                    $written++;
                }
                $this->info("{$written} listing(s) updated.");
            }
        }

        if ($mixed) {
            $this->newLine();
            $this->warn('Bookings for these units went to more than one listing — decide by hand:');
            $this->table(['EZEE unit', 'listings seen'], $mixed);
        }

        return 0;
    }
}

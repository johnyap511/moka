<?php

namespace App\Console\Commands;

use App\EzeeGroup;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Support\EzeeBookingFeed;
use Illuminate\Console\Command;

/**
 * Report every unit EZEE knows about, per property.
 *
 * The backfill only learns a unit's name when a booking we already hold happens
 * to reference it, so it reveals the estate slowly and partially. This asks the
 * same endpoint for the same range but keeps every unit it sees, whether or not
 * we hold the reservation — which is what answering "how many apartments does
 * this property have" actually requires.
 *
 * Read-only: it writes a CSV and prints a summary, and never touches bookings.
 */
class ListEzeeRooms extends Command
{
    protected $signature = 'ezee:list-rooms
                            {--from= : earliest stay date to cover (default: 3 years ago)}
                            {--to= : latest stay date to cover (default: +12 months)}
                            {--hotel= : limit to one hotel code}
                            {--csv= : where to write the unit list (default: storage/app/ezee_rooms.csv)}';

    protected $description = 'List every unit EZEE reports for each property';

    public function handle()
    {
        set_time_limit(0);

        $from  = $this->option('from') ?: date('Y-m-d', strtotime('-3 years'));
        $to    = $this->option('to')   ?: date('Y-m-d', strtotime('+12 months'));
        $only  = $this->option('hotel');
        $csv   = $this->option('csv') ?: storage_path('app/ezee_rooms.csv');

        $this->info("Range {$from} .. {$to}");

        // A unit is claimed by a listing through ezee_room_id, so the same
        // lookup auto-assign uses tells us which units are already spoken for.
        $claimed = Listing::whereNotNull('ezee_room_id')
            ->where('ezee_room_id', '!=', '')
            ->pluck('name', 'ezee_room_id')
            ->mapWithKeys(fn ($name, $id) => [strtolower(trim($id)) => $name]);

        $feed    = new EzeeBookingFeed(fn ($line) => $this->line($line));
        $handle  = fopen($csv, 'w');
        fputcsv($handle, ['hotel_code', 'property', 'unit', 'room_type', 'bookings_in_range', 'mapped_listing']);

        $summary = [];

        foreach (EzeeGroup::all() as $group) {
            if ($only && (string) $group->hotel_code !== (string) $only) {
                continue;
            }

            $this->line("  {$group->hotel_code} {$group->name}");

            $rooms = $feed->roomCatalogue($group, $from, $to);

            if (!$rooms) {
                $this->warn("    no units returned — check the auth key for {$group->hotel_code}");
                $summary[] = [$group->hotel_code, $group->name, 0, 0, 0];
                continue;
            }

            $mapped = 0;
            foreach ($rooms as $unit => $info) {
                $listing = $claimed[strtolower(trim($unit))] ?? '';
                if ($listing !== '') {
                    $mapped++;
                }
                fputcsv($handle, [
                    $group->hotel_code,
                    $group->name,
                    $unit,
                    $info['room_type'],
                    $info['bookings'],
                    $listing,
                ]);
            }

            // What we already hold locally, for comparison with what EZEE says.
            $known = EzeeBooking::where('TransactionId', 'LIKE', $group->hotel_code . '%')
                ->whereNotNull('RoomName')
                ->where('RoomName', '!=', '')
                ->distinct()
                ->count('RoomName');

            $summary[] = [$group->hotel_code, $group->name, count($rooms), $known, $mapped];
        }

        fclose($handle);

        // Every request needs a hotel code and its auth key, so EZEE cannot be
        // asked what properties exist — this command can only visit groups we
        // hold. Bookings whose transaction id carries an unknown prefix are the
        // one hint that a property is missing from ezee_groups entirely.
        $codes  = EzeeGroup::pluck('hotel_code')->map(fn ($c) => (string) $c)->all();
        $orphan = EzeeBooking::selectRaw('LEFT(TransactionId, 5) AS prefix, COUNT(*) AS n')
            ->groupBy('prefix')
            ->pluck('n', 'prefix')
            ->reject(fn ($n, $prefix) => in_array((string) $prefix, $codes, true));

        if ($orphan->isNotEmpty()) {
            $this->newLine();
            $this->warn('Bookings reference hotel codes with no matching ezee_groups row:');
            foreach ($orphan as $prefix => $n) {
                $this->warn("  {$prefix}: {$n} booking(s) — no auth key, so its units cannot be listed");
            }
        }

        $this->newLine();
        $this->table(
            ['Hotel', 'Property', 'Units in EZEE', 'Known locally', 'Mapped to a listing'],
            $summary
        );
        $this->info("Unit list written to {$csv}");

        return 0;
    }
}

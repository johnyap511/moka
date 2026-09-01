<?php

namespace App\Console\Commands;

use App\EzeeGroup;
use App\EzeeRoom;
use App\Support\EzeeRoomFeed;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Refresh ezee_rooms from EZEE, so every unit can be mapped whether or not it
 * has ever been booked.
 */
class SyncEzeeRooms extends Command
{
    protected $signature = 'ezee:sync-rooms
                            {--hotel= : limit to one hotel code}
                            {--dry-run : report what would change without writing}';

    protected $description = 'Pull the full unit list for each property from EZEE';

    public function handle()
    {
        $feed   = new EzeeRoomFeed();
        $dryRun = $this->option('dry-run');
        $only   = $this->option('hotel');
        $rows   = [];

        foreach (EzeeGroup::all() as $group) {
            if ($only && (string) $group->hotel_code !== (string) $only) {
                continue;
            }

            $units = $feed->unitsFor($group);

            if (!$units) {
                $this->warn("  {$group->hotel_code} {$group->name}: no units returned — check the auth key");
                $rows[] = [$group->hotel_code, $group->name, 0, 0, 0];
                continue;
            }

            $created = 0;
            $updated = 0;

            foreach ($units as $unit) {
                $attributes = [
                    'ezee_group_id'  => $group->id,
                    'ezee_room_id'   => $unit['room_id'],
                    'ezee_unit_id'   => $unit['unit_id'],
                    'room_type_name' => $unit['room_type'],
                    'is_blocked'     => $unit['is_blocked'],
                    'last_seen_at'   => Carbon::now(),
                ];

                $existing = EzeeRoom::where('hotel_code', $group->hotel_code)
                    ->where('room_name', $unit['room_name'])
                    ->first();

                $existing ? $updated++ : $created++;

                if (!$dryRun) {
                    EzeeRoom::updateOrCreate(
                        ['hotel_code' => $group->hotel_code, 'room_name' => $unit['room_name']],
                        $attributes
                    );
                }
            }

            $rows[] = [$group->hotel_code, $group->name, count($units), $created, $updated];
        }

        $this->newLine();
        $this->table(['Hotel', 'Property', 'Units', 'New', 'Existing'], $rows);

        $total = array_sum(array_column($rows, 2));
        $this->info(($dryRun ? '[dry run] ' : '') . "{$total} unit(s) reported by EZEE.");

        if ($dryRun) {
            $this->comment('Nothing was written. Re-run without --dry-run to apply.');
        }

        return 0;
    }
}

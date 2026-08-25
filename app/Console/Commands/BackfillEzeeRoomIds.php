<?php

namespace App\Console\Commands;

use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use App\Support\EzeeBookingFeed;
use Illuminate\Console\Command;

/**
 * Fill in the EZEE unit id (eZeePMSRoomid) on bookings that don't have one.
 *
 * The hourly sync now records it, but bookings imported before that fix have it
 * empty, and without it they cannot be matched to a listing.
 *
 * Deliberately touches only unassigned bookings, and only the RoomName column.
 * The full historical sync also rewrites Start, End and the amounts, which
 * would move figures on bookings that have already been assigned and settled.
 */
class BackfillEzeeRoomIds extends Command
{
    protected $signature = 'ezee:backfill-room-ids
                            {--from= : earliest stay date to cover (default: today)}
                            {--to= : latest stay date to cover (default: +18 months)}
                            {--dry-run : report what would change without writing}';

    protected $description = 'Backfill the EZEE unit id on unassigned bookings';

    public function handle()
    {
        set_time_limit(0);

        $from = $this->option('from') ?: date('Y-m-d');
        $to   = $this->option('to')   ?: date('Y-m-d', strtotime('+18 months'));
        $dry  = (bool) $this->option('dry-run');

        $this->info("Range {$from} .. {$to}" . ($dry ? '  (dry run)' : ''));

        $pending = EzeeBooking::query()
            ->where(function ($q) {
                $q->whereNull('book_id')->orWhere('book_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('RoomName')->orWhere('RoomName', '');
            })
            ->whereBetween('Start', [$from, $to])
            ->get(['id', 'SubBookingId', 'TransactionId']);

        if ($pending->isEmpty()) {
            $this->info('Nothing to backfill.');
            return 0;
        }

        $this->info("{$pending->count()} unassigned booking(s) missing a unit id.");

        // EZEE is queried per property, so group by the hotel code that its
        // transaction ids are prefixed with.
        $byHotel = $pending->groupBy(fn ($b) => substr((string) $b->TransactionId, 0, 5));

        $feed    = new EzeeBookingFeed(fn ($line) => $this->line($line));
        $updated = 0;
        $noMatch = 0;

        foreach (EzeeGroup::all() as $group) {
            $rows = $byHotel[(string) $group->hotel_code] ?? collect();
            if ($rows->isEmpty()) {
                continue;
            }

            $this->line("  {$group->hotel_code} {$group->name}: {$rows->count()} pending");

            $map = $feed->roomIds($group, $from, $to);

            if (!$map) {
                continue;
            }

            foreach ($rows as $row) {
                $roomId = $map[$row->SubBookingId] ?? null;
                if ($roomId === null) {
                    $noMatch++;
                    continue;
                }
                if (!$dry) {
                    EzeeBooking::where('id', $row->id)->update(['RoomName' => $roomId]);
                }
                $updated++;
            }

            $this->line("    matched {$updated} so far");
        }

        $this->info(($dry ? 'Would update ' : 'Updated ') . "{$updated} booking(s); {$noMatch} had no match in the EZEE response.");

        return 0;
    }





}

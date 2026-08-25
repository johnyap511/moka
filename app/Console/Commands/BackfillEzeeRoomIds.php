<?php

namespace App\Console\Commands;

use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
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

        $updated = 0;
        $noMatch = 0;

        foreach (EzeeGroup::all() as $group) {
            $rows = $byHotel[(string) $group->hotel_code] ?? collect();
            if ($rows->isEmpty()) {
                continue;
            }

            $this->line("  {$group->hotel_code} {$group->name}: {$rows->count()} pending");

            // EZEE rejects ranges over a year ("Date range should be less then
            // 365 days"), so ask for it in windows and merge the results.
            $map = [];
            $failed = false;

            foreach ($this->windows($from, $to) as [$wFrom, $wTo]) {
                $xml = $this->fetch($group, $wFrom, $wTo);

                if ($xml === null) {
                    $this->warn("    {$wFrom}..{$wTo}: request failed");
                    $failed = true;
                    continue;
                }

                if ($error = $this->errorFrom($xml)) {
                    $this->warn("    {$wFrom}..{$wTo}: EZEE said: {$error}");
                    $failed = true;
                    continue;
                }

                $map += $this->roomIdsBySubBooking($xml);
            }

            if (!$map) {
                if (!$failed) {
                    $this->warn('    response carried no unit ids');
                }
                continue;
            }

            $this->line('    ' . count($map) . ' unit id(s) returned by EZEE');

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

    private function fetch(EzeeGroup $group, string $from, string $to): ?string
    {
        $body = '<RES_Request>
            <Request_Type>Booking</Request_Type>
            <Authentication>
                <HotelCode>' . $group->hotel_code . '</HotelCode>
                <AuthCode>' . $group->auth_key . '</AuthCode>
            </Authentication>
            <FromDate>' . $from . '</FromDate>
            <ToDate>' . $to . '</ToDate>
        </RES_Request>';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://live.ipms247.com/pmsinterface/getdataAPI.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
        ]);
        $response = curl_exec($ch);
        $failed   = curl_errno($ch) !== 0;
        curl_close($ch);

        return $failed ? null : $response;
    }

    /**
     * Pull SubBookingId => eZeePMSRoomid out of the response.
     *
     * Scanned directly rather than run through simplexml: only two fields per
     * booking are needed, and the sync's full parse is sensitive to how EZEE
     * collapses single-element lists.
     *
     * @return array<string,string>
     */
    private function roomIdsBySubBooking(string $xml): array
    {
        $map = [];

        if (!preg_match_all('#<BookingTran>(.*?)</BookingTran>#s', $xml, $blocks)) {
            return $map;
        }

        foreach ($blocks[1] as $block) {
            if (!preg_match('#<SubBookingId>([^<]*)</SubBookingId>#', $block, $sub)) {
                continue;
            }
            if (!preg_match('#<eZeePMSRoomid>([^<]*)</eZeePMSRoomid>#', $block, $room)) {
                continue;
            }
            $roomId = trim($room[1]);
            if ($roomId !== '') {
                $map[trim($sub[1])] = $roomId;
            }
        }

        return $map;
    }

    /**
     * Split a date range into windows EZEE will accept.
     *
     * @return array<array{0:string,1:string}>
     */
    private function windows(string $from, string $to, int $days = 360): array
    {
        $windows = [];
        $start   = strtotime($from);
        $end     = strtotime($to);

        while ($start <= $end) {
            $stop = min($end, strtotime("+{$days} days", $start));
            $windows[] = [date('Y-m-d', $start), date('Y-m-d', $stop)];
            $start = strtotime('+1 day', $stop);
        }

        return $windows;
    }

    /**
     * EZEE reports problems as either <error> or an Errors block, and returns
     * HTTP 200 either way — so failures are invisible unless read out.
     */
    private function errorFrom(string $xml): ?string
    {
        if (preg_match('#<error>([^<]*)</error>#i', $xml, $m)) {
            return trim($m[1]);
        }
        if (preg_match('#"ErrorMessage"\s*:\s*"([^"]*)"#', $xml, $m)) {
            return trim($m[1]);
        }
        if (preg_match('#<ErrorMessage>([^<]*)</ErrorMessage>#', $xml, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}

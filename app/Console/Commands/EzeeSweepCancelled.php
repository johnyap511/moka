<?php

namespace App\Console\Commands;

use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Retires reservations EZEE has cancelled.
 *
 * EZEE never reports a cancellation — a cancelled reservation simply stops
 * appearing in the booking API, so the only signal is absence. Nothing ever
 * re-checked an imported booking, and 433 dead reservations had built up in the
 * review queue, with cancelled bookings still occupying units and blocking real
 * ones.
 *
 * Absence is only meaningful from a complete response. A throttled or truncated
 * pull looks exactly like every reservation being cancelled, so a short response
 * skips the property rather than emptying it.
 */
class EzeeSweepCancelled extends Command
{
    protected $signature = 'ezee:sweep-cancelled
                            {--from= : earliest stay date to cover (default: 30 days ago)}
                            {--to= : latest stay date to cover (default: +90 days)}
                            {--retire-assigned : also cancel bookings already on an owner calendar}
                            {--dry-run : report what would change without writing}';

    protected $description = 'Retire reservations EZEE no longer has, which it never reports directly';

    public function handle(): int
    {
        set_time_limit(0);

        $from   = $this->option('from') ?: now()->subDays(30)->toDateString();
        $to     = $this->option('to')   ?: now()->addDays(90)->toDateString();
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Sweeping {$from} to {$to}" . ($dryRun ? ' (dry run)' : ''));

        $rows = []; $totalCleared = 0; $flagged = [];
        $groups = EzeeGroup::whereNotNull('auth_key')->orderBy('hotel_code')->get();

        foreach ($groups as $i => $group) {
            if ($i) {
                sleep(65); // EZEE rejects rapid repeat requests
            }

            $live = $this->pull($group, $from, $to);
            $held = EzeeBooking::whereRaw('SUBSTR(TransactionId,1,5) = ?', [$group->hotel_code])
                ->whereBetween('Start', [$from, $to])->count();

            // A short response proves nothing. Skip rather than treat every
            // reservation at this property as cancelled.
            if ($live === null || count($live) < $held * 0.5) {
                $rows[] = [$group->hotel_code, substr($group->name, 0, 22), $live === null ? 'failed' : count($live), $held, 'skipped', '—'];
                $this->warn("  {$group->hotel_code}: short response, skipped");
                continue;
            }

            [$cleared, $stillLive] = $this->retire($group->hotel_code, $live, $from, $to, $dryRun, $flagged);
            $rows[] = [$group->hotel_code, substr($group->name, 0, 22), count($live), $held, $cleared, $stillLive];
            $totalCleared += $cleared;
        }

        $this->table(['Hotel', 'Property', 'Returned', 'Held', 'Cancelled', 'Still live'], $rows);
        $this->info(($dryRun ? 'Would retire ' : 'Retired ') . $totalCleared . ' reservation(s).');

        if ($flagged) {
            $this->newLine();
            $this->warn(count($flagged) . ' booking(s) are cancelled in EZEE but still on an owner calendar:');
            foreach (array_slice($flagged, 0, 25) as $line) {
                $this->line('  ' . $line);
            }
            if (count($flagged) > 25) {
                $this->line('  ...and ' . (count($flagged) - 25) . ' more');
            }
            $this->newLine();
            $this->line('Re-run with --retire-assigned to cancel these too. Left alone by default,');
            $this->line('because cancelling one removes revenue from an owner statement.');
        }

        return self::SUCCESS;
    }

    /** @return array<string,true>|null null when the request failed */
    private function pull(EzeeGroup $group, string $from, string $to): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://live.ipms247.com/pmsinterface/getdataAPI.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 240,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => '<RES_Request><Request_Type>Booking</Request_Type><Authentication>'
                . "<HotelCode>{$group->hotel_code}</HotelCode><AuthCode>{$group->auth_key}</AuthCode>"
                . "</Authentication><FromDate>{$from}</FromDate><ToDate>{$to}</ToDate></RES_Request>",
            CURLOPT_HTTPHEADER     => ['Content-Type: application/xml'],
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $xml = @simplexml_load_string(trim((string) $body));

        if (!$xml) {
            return null;
        }

        $live = [];
        foreach ($xml->xpath('//BookingTran') ?: [] as $node) {
            $row = json_decode(json_encode($node), true);
            if (!empty($row['SubBookingId'])) {
                $live[$row['SubBookingId']] = true;
            }
        }

        return $live;
    }

    /** @return array{0:int,1:int} */
    private function retire(string $hotel, array $live, string $from, string $to, bool $dryRun, array &$flagged): array
    {
        $cleared = 0; $stillLive = 0;

        $rows = EzeeBooking::whereRaw('SUBSTR(TransactionId,1,5) = ?', [$hotel])
            ->whereBetween('Start', [$from, $to])
            ->where('status', '<>', 1)
            ->get(['id', 'SubBookingId', 'book_id', 'Start', 'End', 'FirstName', 'LastName']);

        foreach ($rows as $row) {
            // A reservation EZEE sends without a number cannot be matched to
            // the pull by number, so its absence proves nothing. Left alone:
            // one such tenancy was retired this way while still live in EZEE.
            if ($row->SubBookingId === null || trim($row->SubBookingId) === '') {
                $stillLive++;
                continue;
            }

            if (isset($live[$row->SubBookingId])) {
                $stillLive++;
                continue;
            }

            $guest = trim($row->FirstName . ' ' . $row->LastName);

            if ($row->book_id) {
                $booking = DB::table('bookings')->where('id', $row->book_id)->where('status', '<>', 1)->first();

                if ($booking && !$this->option('retire-assigned')) {
                    $flagged[] = "#{$booking->id} {$row->SubBookingId} {$row->Start}..{$row->End} {$guest}";
                    continue;
                }

                if ($booking && !$dryRun) {
                    DB::table('bookings')->where('id', $booking->id)->update(['status' => 1, 'updated_at' => now()]);
                }
            }

            if (!$dryRun) {
                DB::table('ezee_bookings')->where('id', $row->id)->update(['status' => 1, 'updated_at' => now()]);
            }

            $cleared++;
        }

        return [$cleared, $stillLive];
    }
}

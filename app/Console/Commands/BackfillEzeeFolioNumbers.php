<?php

namespace App\Console\Commands;

use App\Booking;
use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use Illuminate\Console\Command;

class BackfillEzeeFolioNumbers extends Command
{
    protected $signature = 'ezee:fill-folio-numbers
                            {--missing-only : Only fetch for bookings without folio_no (default)}
                            {--all : Re-fetch folio for all bookings, even those already populated}';

    protected $description = 'Fetch FN#### folio numbers from EZEE for all existing ezee_bookings records';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $refetchAll = $this->option('all');

        $query = EzeeBooking::whereNotNull('SubBookingId');
        if (!$refetchAll) {
            $query->whereNull('folio_no');
        }

        $total = $query->count();
        $this->info("Fetching folio numbers for {$total} bookings...");

        // Build a map of hotel_code => auth_key from EzeeGroup
        $groups = EzeeGroup::all()->keyBy('hotel_code');
        $defaultGroup = $groups->first();

        $done = 0;
        $filled = 0;

        $query->chunk(100, function ($bookings) use ($groups, $defaultGroup, &$done, &$filled) {
            foreach ($bookings as $booking) {
                $done++;

                // Determine which hotel/auth to use
                $group = $defaultGroup;
                $hotelCode = $group->hotel_code;
                $authKey   = $group->auth_key;

                $payload = json_encode([
                    'RES_Request' => [
                        'Request_Type'   => 'RetrieveListofBills',
                        'Authentication' => [
                            'HotelCode' => $hotelCode,
                            'AuthCode'  => $authKey,
                            'BookingId' => $booking->SubBookingId,
                        ],
                    ],
                ]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://live.ipms247.com/index.php/page/service.kioskconnectivity');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                curl_close($ch);

                $res = json_decode($response, true);

                if (isset($res['Success']['FolioList'][0]['foliono'])) {
                    $folioNo = $res['Success']['FolioList'][0]['foliono'];
                    $booking->update(['folio_no' => $folioNo]);
                    if ($booking->book_id) {
                        Booking::where('id', $booking->book_id)->update(['server_folio_no' => $folioNo]);
                    }
                    $filled++;
                }

                if ($done % 100 === 0) {
                    $this->line("  Progress: {$done}/{$total} processed, {$filled} folio numbers filled");
                }

                usleep(50000); // 0.05s between calls
            }
        });

        $this->info("Done. Processed: {$done} | Folio numbers filled: {$filled}");
    }
}

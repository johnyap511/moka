<?php

namespace App\Console\Commands;

use App\Booking;
use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use Illuminate\Console\Command;

class BackfillEzeeFolioNo extends Command
{
    protected $signature = 'ezee:backfill-folio';
    protected $description = 'Fetch folio numbers from EZEE for all ezee_bookings missing folio_no';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $groups = EzeeGroup::all()->keyBy('id');

        $bookings = EzeeBooking::whereNull('folio_no')->whereNotNull('SubBookingId')->get();
        $total    = $bookings->count();

        $this->info("Found {$total} bookings without folio number. Starting...");

        $success = 0;
        $failed  = 0;

        foreach ($bookings as $booking) {
            $group     = $groups->get($booking->ezee_group_id);
            $hotelCode = $group ? $group->hotel_code : 19676;
            $authKey   = $group ? $group->auth_key   : '7181420090112972af-41e8-11ec-9';

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
                EzeeBooking::where('id', $booking->id)->update(['folio_no' => $folioNo]);
                if ($booking->book_id) {
                    Booking::where('id', $booking->book_id)->update(['server_folio_no' => $folioNo]);
                }
                $success++;
            } else {
                $failed++;
            }

            // Small pause to avoid hammering EZEE API
            usleep(100000); // 0.1 second between calls
        }

        $this->info("Done. Success: {$success} | No folio returned: {$failed}");
    }
}

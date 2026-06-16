<?php

namespace App\Console\Commands;

use App\Booking;
use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use Illuminate\Console\Command;

class BackfillEzeeFolioNo extends Command
{
    protected $signature = 'ezee:backfill-folio
                            {--from=2018-01-01 : Start date for booking fetch}
                            {--to= : End date for booking fetch (defaults to 2 years from now)}';

    protected $description = 'Full re-sync all EZEE bookings (correct all data + fetch folio numbers)';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $fromDate = $this->option('from');
        $toDate   = $this->option('to') ?: date('Y-m-d', strtotime('+2 years'));

        $this->info("Re-syncing EZEE bookings from {$fromDate} to {$toDate}");

        $listings = EzeeGroup::all();
        $newCount = 0;
        $updatedCount = 0;

        // Split into 6-month chunks to stay within EZEE's date range limit
        $chunks = [];
        $cursor = new \DateTime($fromDate);
        $end    = new \DateTime($toDate);
        while ($cursor <= $end) {
            $chunkEnd = (clone $cursor)->modify('+6 months')->modify('-1 day');
            if ($chunkEnd > $end) $chunkEnd = clone $end;
            $chunks[] = [$cursor->format('Y-m-d'), $chunkEnd->format('Y-m-d')];
            $cursor->modify('+6 months');
        }

        foreach ($listings as $listing) {
            $this->info("Processing group: {$listing->name} (hotel: {$listing->hotel_code})");

            foreach ($chunks as [$chunkFrom, $chunkTo]) {
                $this->line("  Fetching {$chunkFrom} → {$chunkTo}");

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => 'https://live.ipms247.com/pmsinterface/getdataAPI.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 120,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => '<RES_Request>
                        <Request_Type>Booking</Request_Type>
                        <Authentication>
                            <HotelCode>' . $listing->hotel_code . '</HotelCode>
                            <AuthCode>' . $listing->auth_key . '</AuthCode>
                        </Authentication>
                        <FromDate>' . $chunkFrom . '</FromDate>
                        <ToDate>' . $chunkTo . '</ToDate>
                    </RES_Request>',
                    CURLOPT_HTTPHEADER => ['Content-Type: application/xml'],
                ]);

                $response = curl_exec($curl);
                curl_close($curl);

                $xml = simplexml_load_string(trim($response));
                if (!$xml) {
                    $this->warn("    No valid XML response");
                    continue;
                }

                $res = json_decode(json_encode($xml), true);
                if (!is_array($res)) {
                    $this->warn("    Failed to parse XML response");
                    continue;
                }

                $reservationCount = 0;
                foreach ($res as $reservation) {
                    if (!is_array($reservation) || !array_key_exists('Reservation', $reservation)) continue;
                    $reservationCount += count($reservation['Reservation']);
                }
                $this->line("    Found {$reservationCount} reservations");

                foreach ($res as $reservation) {
                    if (!is_array($reservation) || !array_key_exists('Reservation', $reservation)) continue;

                foreach ($reservation['Reservation'] as $reserve) {
                    if (array_key_exists('BookingTran', $reserve)) {
                        $reserve = ['BookByInfo' => $reserve];
                    }
                    if (!array_key_exists('BookByInfo', $reserve) || !array_key_exists('BookingTran', $reserve['BookByInfo'])) continue;

                    foreach ($reserve as $reserve1) {
                        if (!is_array($reserve1) || !array_key_exists('BookingTran', $reserve1)) continue;
                        $tran = $reserve1['BookingTran'];

                        // Single BookingTran: has 'IsConfirmed' key directly
                        if (array_key_exists('IsConfirmed', $tran)) {
                            $trans = [$tran];
                        } else {
                            // Multiple BookingTrans: numerically indexed array
                            $trans = $tran;
                        }

                        foreach ($trans as $t) {
                            if (!array_key_exists('IsConfirmed', $t)) continue;

                            $sub_booking_id = !is_array($t['SubBookingId'] ?? null) ? ($t['SubBookingId'] ?? null) : null;
                            if (!$sub_booking_id) continue;

                            $data = [
                                'TransactionId'        => !is_array($t['TransactionId']      ?? null) ? ($t['TransactionId']      ?? null) : null,
                                'IsConfirmed'          => !is_array($t['IsConfirmed']         ?? null) ? ($t['IsConfirmed']         ?? null) : null,
                                'RateplanName'         => !is_array($t['RateplanName']        ?? null) ? ($t['RateplanName']        ?? null) : null,
                                'RoomTypeName'         => !is_array($t['RoomTypeName']        ?? null) ? ($t['RoomTypeName']        ?? null) : null,
                                'RoomName'             => isset($t['RoomName']) && !is_array($t['RoomName']) ? $t['RoomName'] : null,
                                'Start'                => !is_array($t['Start']               ?? null) ? ($t['Start']               ?? null) : null,
                                'End'                  => !is_array($t['End']                 ?? null) ? ($t['End']                 ?? null) : null,
                                'CurrencyCode'         => !is_array($t['CurrencyCode']        ?? null) ? ($t['CurrencyCode']        ?? null) : null,
                                'TotalAmountAfterTax'  => !is_array($t['TotalAmountAfterTax'] ?? null) ? ($t['TotalAmountAfterTax'] ?? null) : null,
                                'TotalAmountBeforeTax' => !is_array($t['TotalAmountBeforeTax']?? null) ? ($t['TotalAmountBeforeTax']?? null) : null,
                                'TotalDiscount'        => !is_array($t['TotalDiscount']       ?? null) ? ($t['TotalDiscount']       ?? null) : null,
                                'TotalExtraCharge'     => !is_array($t['TotalExtraCharge']    ?? null) ? ($t['TotalExtraCharge']    ?? null) : null,
                                'TotalPayment'         => !is_array($t['TotalPayment']        ?? null) ? ($t['TotalPayment']        ?? null) : null,
                                'TACommision'          => !is_array($t['TACommision']         ?? null) ? ($t['TACommision']         ?? null) : null,
                                'FirstName'            => isset($reserve1['FirstName'])  && !is_array($reserve1['FirstName'])  ? $reserve1['FirstName']  : null,
                                'LastName'             => isset($reserve1['LastName'])   && !is_array($reserve1['LastName'])   ? $reserve1['LastName']   : null,
                                'Mobile'               => isset($reserve1['Mobile'])     && !is_array($reserve1['Mobile'])     ? $reserve1['Mobile']     : null,
                                'Email'                => isset($reserve1['Email'])      && !is_array($reserve1['Email'])      ? $reserve1['Email']      : null,
                                'Country'              => isset($reserve1['Country'])    && !is_array($reserve1['Country'])    ? $reserve1['Country']    : null,
                                'Source'               => isset($t['BookedBy'])          && !is_array($t['BookedBy'])          ? preg_replace('/[^A-Za-z\. ]/', '', $t['BookedBy']) : null,
                            ];

                            $exist = EzeeBooking::where('SubBookingId', $sub_booking_id)->first();

                            if (!$exist) {
                                EzeeBooking::create(array_merge(['SubBookingId' => $sub_booking_id, 'created_at' => $t['Createdatetime'] ?? null], $data));
                                $newCount++;
                            } else {
                                $exist->update($data);
                                $updatedCount++;
                            }

                            // Fetch folio number for this booking
                            $this->fetchAndStoreFolio($sub_booking_id, $listing->hotel_code, $listing->auth_key);

                            usleep(100000); // 0.1s between calls
                        }
                    }
                }
                } // end res loop
            } // end chunks loop

            $this->info("  Done. New: {$newCount} | Updated: {$updatedCount}");
        } // end listings loop

        $this->info("Full re-sync complete. New: {$newCount} | Updated: {$updatedCount}");
    }

    private function fetchAndStoreFolio($subBookingId, $hotelCode, $authKey)
    {
        $payload = json_encode([
            'RES_Request' => [
                'Request_Type'   => 'RetrieveListofBills',
                'Authentication' => [
                    'HotelCode' => $hotelCode,
                    'AuthCode'  => $authKey,
                    'BookingId' => $subBookingId,
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
            $booking = EzeeBooking::where('SubBookingId', $subBookingId)->first();
            if ($booking) {
                $booking->update(['folio_no' => $folioNo]);
                if ($booking->book_id) {
                    Booking::where('id', $booking->book_id)->update(['server_folio_no' => $folioNo]);
                }
            }
        }
    }
}

<?php

namespace App\Console\Commands;
use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use App\DataLog;
use App\Booking;
use Illuminate\Console\Command;

class HistoricalApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hour:update';

    // protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        // echo "hi";die;
        // ini_set('max_execution_time', 300);
        set_time_limit(0);
        $listings = EzeeGroup::all();
        $date_current = date("Y-m-d");

        $newDate = date("Y-m-d", strtotime("-3 days"));
        $futureDate = date("Y-m-d", strtotime("+6 months"));
        $first_date_of_month = date("Y-m-01");

        $new_date_folio = date("Y-m-d", strtotime("-30 days"));

        $ezee_booking_folio = EzeeBooking::whereBetween('Start', [$new_date_folio, $futureDate])->whereNull('folio_no')->orderBy('Start', 'desc')->limit(50)->get();
        $postData_F['Request_Type'] = 'RetrieveListofBills';
        foreach ($ezee_booking_folio as $get_folio_no) {
            $postData_F['Authentication'] = [
                'HotelCode' => 19676,
                'AuthCode' => '7010306964bf04d4ef-9225-11f1-8',
                'BookingId' => $get_folio_no->SubBookingId,
            ];

            $pd_f['RES_Request'] = $postData_F;
            $payload_f = json_encode($pd_f);
            $ch_f = curl_init();
            curl_setopt($ch_f, CURLOPT_URL, "https://live.ipms247.com/index.php/page/service.kioskconnectivity");
            curl_setopt($ch_f, CURLOPT_POST, 1);
            curl_setopt($ch_f, CURLOPT_POSTFIELDS, $payload_f);
            curl_setopt($ch_f, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch_f, CURLOPT_RETURNTRANSFER, true);
            $server_output_f = curl_exec($ch_f);
            curl_close($ch_f);
            $res_f = json_decode($server_output_f, true);

            if (isset($res_f['Success']['FolioList'][0]['foliono'])) {
                $folioNo = $res_f['Success']['FolioList'][0]['foliono'];
                EzeeBooking::where('id', $get_folio_no->id)->update(['folio_no' => $folioNo]);
                if ($get_folio_no->book_id) {
                    Booking::where('id', $get_folio_no->book_id)->update(['server_folio_no' => $folioNo]);
                }
            }
        }

        foreach ($listings as $listing) {
            $xml_response = array();
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://live.ipms247.com/pmsinterface/getdataAPI.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '<RES_Request>
        <Request_Type>Booking</Request_Type>
            <Authentication>
            <HotelCode>' . $listing->hotel_code . '</HotelCode>
            <AuthCode>' . $listing->auth_key . '</AuthCode>
            </Authentication>
            <FromDate>' . $newDate . '</FromDate>
            <ToDate>' . $futureDate . '</ToDate>
        </RES_Request>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml',
                    'Cookie: AWSALB=7rZ/rDok4zKkcbOzsmCsjFd9Wd7WF91XvvLA9j91IpkFz8SDgQ02uIarkeLrm57vOkI+foqTGxVZH1hK+XeiFoPLHbZydZMrC0wC+ncgOxLCrwoDKWBVvdeET4tk; AWSALBCORS=7rZ/rDok4zKkcbOzsmCsjFd9Wd7WF91XvvLA9j91IpkFz8SDgQ02uIarkeLrm57vOkI+foqTGxVZH1hK+XeiFoPLHbZydZMrC0wC+ncgOxLCrwoDKWBVvdeET4tk; SSID=ri5l2lighfde46pq1du9ngvoen'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string(trim($response));
            libxml_clear_errors();
            $json = json_encode($xml);
            $res = json_decode($json, TRUE);
            $reservation_data_ezee = array();
            // dd($res);
            if (is_array($res)) {
                foreach ($res as $reservation) {

                    if (is_array($reservation) && array_key_exists('Reservation', $reservation) && !empty($reservation)) {
                        $i = 0;

                        foreach ($reservation['Reservation'] as $reserve) {
                            //adding key to single array
                            if (array_key_exists('BookingTran', $reserve)) {
                                $test_array['BookByInfo'] = $reserve;
                                $reserve = $test_array;
                            }
                            if (array_key_exists('BookingTran', $reserve['BookByInfo'])) {
                                //BookByInfo loop
                                // dd($reserve);
                                foreach ($reserve as $reserve1) {
                                    //getting key if exist to find array inside array or elements
                                    if (array_key_exists('IsConfirmed', $reserve1['BookingTran'])) {
                                        if (is_array($reserve1['BookingTran']['SubBookingId'])) {
                                            $sub_booking_id = NULL;
                                        } else {
                                            $sub_booking_id = $reserve1['BookingTran']['SubBookingId'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TransactionId'])) {
                                            $transaction_id = NULL;
                                        } else {
                                            $transaction_id = $reserve1['BookingTran']['TransactionId'];
                                        }

                                        if (is_array($reserve1['BookingTran']['IsConfirmed'])) {
                                            $is_confirmed = NULL;
                                        } else {
                                            $is_confirmed = $reserve1['BookingTran']['IsConfirmed'];
                                        }

                                        if (is_array($reserve1['BookingTran']['RateplanName'])) {
                                            $rateplanName = NULL;
                                        } else {
                                            $rateplanName = $reserve1['BookingTran']['RateplanName'];
                                        }

                                        if (is_array($reserve1['BookingTran']['RoomTypeName'])) {
                                            $roomTypeName = NULL;
                                        } else {
                                            $roomTypeName = $reserve1['BookingTran']['RoomTypeName'];
                                        }

                                        if (isset($reserve1['BookingTran']['RoomName']) && !is_array($reserve1['BookingTran']['RoomName'])) {
                                            $roomName = $reserve1['BookingTran']['RoomName'];
                                        } else {
                                            $roomName = NULL;
                                        }

                                        if (is_array($reserve1['BookingTran']['Createdatetime'])) {
                                            $created_at = NULL;
                                        } else {
                                            $created_at = $reserve1['BookingTran']['Createdatetime'];
                                        }

                                        if (is_array($reserve1['BookingTran']['Start'])) {
                                            $start = NULL;
                                        } else {
                                            $start = $reserve1['BookingTran']['Start'];
                                        }

                                        if (is_array($reserve1['BookingTran']['End'])) {
                                            $end = NULL;
                                        } else {
                                            $end = $reserve1['BookingTran']['End'];
                                        }

                                        if (is_array($reserve1['BookingTran']['CurrencyCode'])) {
                                            $currencyCode = NULL;
                                        } else {
                                            $currencyCode = $reserve1['BookingTran']['CurrencyCode'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalAmountAfterTax'])) {
                                            $totalAmountAfterTax = NULL;
                                        } else {
                                            $totalAmountAfterTax = $reserve1['BookingTran']['TotalAmountAfterTax'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalAmountBeforeTax'])) {
                                            $totalAmountBeforeTax = NULL;
                                        } else {
                                            $totalAmountBeforeTax = $reserve1['BookingTran']['TotalAmountBeforeTax'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalDiscount'])) {
                                            $totalDiscount = NULL;
                                        } else {
                                            $totalDiscount = $reserve1['BookingTran']['TotalDiscount'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalExtraCharge'])) {
                                            $totalExtraCharge = NULL;
                                        } else {
                                            $totalExtraCharge = $reserve1['BookingTran']['TotalExtraCharge'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalPayment'])) {
                                            $totalPayment = NULL;
                                        } else {
                                            $totalPayment = $reserve1['BookingTran']['TotalPayment'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TACommision'])) {
                                            $tACommision = NULL;
                                        } else {
                                            $tACommision = $reserve1['BookingTran']['TACommision'];
                                        }


                                        if (is_array($reserve1['FirstName'])) {
                                            $first_name = NULL;
                                        } else {
                                            $first_name = $reserve1['FirstName'];
                                        }

                                        if (is_array($reserve1['LastName'])) {
                                            $last_name = NULL;
                                        } else {
                                            $last_name = $reserve1['LastName'];
                                        }

                                        if (is_array($reserve1['Mobile'])) {
                                            $mobile = NULL;
                                        } else {
                                            $mobile = $reserve1['Mobile'];
                                        }

                                        if (is_array($reserve1['Email'])) {
                                            $email = NULL;
                                        } else {
                                            $email = $reserve1['Email'];
                                        }

                                        if (is_array($reserve1['Country'])) {
                                            $country = NULL;
                                        } else {
                                            $country = $reserve1['Country'];
                                        }

                                        if (is_array($reserve1['BookingTran']['Source'])) {
                                            $source = NULL;
                                        } else {
                                            $source = $reserve1['BookingTran']['Source'];
                                        }

                                        $exist = EzeeBooking::where(
                                            [
                                                ['SubBookingId', $sub_booking_id],
                                                ['TransactionId', $transaction_id]
                                            ]
                                        )->first();
                                        if (empty($exist)) {
                                            $dataLog = DataLog::create([
                                                'title' => 'sendnotify',
                                                'data' => '',
                                                'related_id' => 20317,
                                                'status' => 'started'
                                            ]);
                                            if ($sub_booking_id) {
                                                $exist = EzeeBooking::create([
                                                    'SubBookingId' => $sub_booking_id,
                                                    'TransactionId' => $transaction_id,
                                                    'IsConfirmed' => $is_confirmed,
                                                    'RateplanName' => $rateplanName,
                                                    'RoomTypeName' => $roomTypeName,
                                                    'RoomName' => $roomName,
                                                    'Start' => $start, 'End' => $end,
                                                    'CurrencyCode' => $currencyCode,
                                                    'TotalAmountAfterTax' => $totalAmountAfterTax,
                                                    'TotalAmountBeforeTax' => $totalAmountBeforeTax,
                                                    'TotalDiscount' => $totalDiscount,
                                                    'TotalExtraCharge' => $totalExtraCharge,
                                                    'TotalPayment' => $totalPayment,
                                                    'TACommision' => $tACommision,
                                                    'FirstName' => $first_name,
                                                    'LastName' => $last_name,
                                                    'Mobile' => $mobile,
                                                    'Email' => $email,
                                                    'Country' => $country,
                                                    'Source' => $source,
                                                    'created_at' => $created_at
                                                ]);
                                                $this->fetchFolioForBooking($sub_booking_id, $listing->hotel_code, $listing->auth_key);
                                            }
                                        } else {
                                            EzeeBooking::where("SubBookingId", $sub_booking_id)
                                                ->update([
                                                    'RoomTypeName' => $roomTypeName,
                                                    'RoomName' => $roomName,
                                                    'TotalExtraCharge' => $totalExtraCharge,
                                                    'TotalAmountAfterTax' => $totalAmountAfterTax,
                                                ]);
                                            if ($sub_booking_id && !$exist->folio_no) {
                                                $this->fetchFolioForBooking($sub_booking_id, $listing->hotel_code, $listing->auth_key);
                                            }
                                        }
                                    } else {
                                        foreach ($reserve1['BookingTran'] as $reserve_array_value) {

                                            if (is_array($reserve_array_value['SubBookingId'])) {
                                                $sub_booking_id = NULL;
                                            } else {
                                                $sub_booking_id = $reserve_array_value['SubBookingId'];
                                            }

                                            if (is_array($reserve_array_value['TransactionId'])) {
                                                $transaction_id = NULL;
                                            } else {
                                                $transaction_id = $reserve_array_value['TransactionId'];
                                            }

                                            if (is_array($reserve_array_value['IsConfirmed'])) {
                                                $is_confirmed = NULL;
                                            } else {
                                                $is_confirmed = $reserve_array_value['IsConfirmed'];
                                            }

                                            if (is_array($reserve_array_value['RateplanName'])) {
                                                $rateplanName = NULL;
                                            } else {
                                                $rateplanName = $reserve_array_value['RateplanName'];
                                            }

                                            if (is_array($reserve_array_value['RoomTypeName'])) {
                                                $roomTypeName = NULL;
                                            } else {
                                                $roomTypeName = $reserve_array_value['RoomTypeName'];
                                            }

                                            if (isset($reserve_array_value['RoomName']) && !is_array($reserve_array_value['RoomName'])) {
                                                $roomName = $reserve_array_value['RoomName'];
                                            } else {
                                                $roomName = NULL;
                                            }

                                            if (is_array($reserve_array_value['Start'])) {
                                                $start = NULL;
                                            } else {
                                                $start = $reserve_array_value['Start'];
                                            }

                                            if (is_array($reserve_array_value['End'])) {
                                                $end = NULL;
                                            } else {
                                                $end = $reserve_array_value['End'];
                                            }

                                            if (is_array($reserve_array_value['CurrencyCode'])) {
                                                $currencyCode = NULL;
                                            } else {
                                                $currencyCode = $reserve_array_value['CurrencyCode'];
                                            }

                                            if (is_array($reserve_array_value['TotalAmountAfterTax'])) {
                                                $totalAmountAfterTax = NULL;
                                            } else {
                                                $totalAmountAfterTax = $reserve_array_value['TotalAmountAfterTax'];
                                            }

                                            if (is_array($reserve_array_value['TotalAmountBeforeTax'])) {
                                                $totalAmountBeforeTax = NULL;
                                            } else {
                                                $totalAmountBeforeTax = $reserve_array_value['TotalAmountBeforeTax'];
                                            }

                                            if (is_array($reserve_array_value['TotalDiscount'])) {
                                                $totalDiscount = NULL;
                                            } else {
                                                $totalDiscount = $reserve_array_value['TotalDiscount'];
                                            }

                                            if (is_array($reserve_array_value['TotalExtraCharge'])) {
                                                $totalExtraCharge = NULL;
                                            } else {
                                                $totalExtraCharge = $reserve_array_value['TotalExtraCharge'];
                                            }

                                            if (is_array($reserve_array_value['TotalPayment'])) {
                                                $totalPayment = NULL;
                                            } else {
                                                $totalPayment = $reserve_array_value['TotalPayment'];
                                            }

                                            if (is_array($reserve_array_value['TACommision'])) {
                                                $tACommision = NULL;
                                            } else {
                                                $tACommision = $reserve_array_value['TACommision'];
                                            }


                                            if (is_array($reserve1['FirstName'])) {
                                                $first_name = NULL;
                                            } else {
                                                $first_name = $reserve1['FirstName'];
                                            }

                                            if (is_array($reserve1['LastName'])) {
                                                $last_name = NULL;
                                            } else {
                                                $last_name = $reserve1['LastName'];
                                            }

                                            if (is_array($reserve1['Mobile'])) {
                                                $mobile = NULL;
                                            } else {
                                                $mobile = $reserve1['Mobile'];
                                            }

                                            if (is_array($reserve1['Email'])) {
                                                $email = NULL;
                                            } else {
                                                $email = $reserve1['Email'];
                                            }

                                            if (is_array($reserve1['Country'])) {
                                                $country = NULL;
                                            } else {
                                                $country = $reserve1['Country'];
                                            }

                                            if (is_array($reserve_array_value['Source'])) {
                                                $source = NULL;
                                            } else {
                                                $source = $reserve_array_value['Source'];
                                            }

                                            if (is_array($reserve_array_value['Createdatetime'])) {
                                                $created_at = NULL;
                                            } else {
                                                $created_at = $reserve_array_value['Createdatetime'];
                                            }

                                            $exist = EzeeBooking::where(
                                                [
                                                    ['SubBookingId', $sub_booking_id],
                                                    ['TransactionId', $transaction_id]
                                                ]
                                            )->first();

                                            if (empty($exist)) {
                                                $dataLog = DataLog::create([
                                                    'title' => 'sendnotify',
                                                    'data' => '',
                                                    'related_id' => 20317,
                                                    'status' => 'started'
                                                ]);
                                                if ($sub_booking_id) {

                                                    $exist = EzeeBooking::create([
                                                        'SubBookingId' => $sub_booking_id,
                                                        'TransactionId' => $transaction_id,
                                                        'IsConfirmed' => $is_confirmed,
                                                        'RateplanName' => $rateplanName,
                                                        'RoomTypeName' => $roomTypeName,
                                                        'RoomName' => $roomName,
                                                        'Start' => $start, 'End' => $end,
                                                        'CurrencyCode' => $currencyCode,
                                                        'TotalAmountAfterTax' => $totalAmountAfterTax,
                                                        'TotalAmountBeforeTax' => $totalAmountBeforeTax,
                                                        'TotalDiscount' => $totalDiscount,
                                                        'TotalExtraCharge' => $totalExtraCharge,
                                                        'TotalPayment' => $totalPayment,
                                                        'TACommision' => $tACommision,
                                                        'FirstName' => $first_name,
                                                        'LastName' => $last_name,
                                                        'Mobile' => $mobile,
                                                        'Email' => $email,
                                                        'Country' => $country,
                                                        'Source' => $source,
                                                        'created_at' => $created_at
                                                    ]);
                                                    $this->fetchFolioForBooking($sub_booking_id, $listing->hotel_code, $listing->auth_key);
                                                }
                                            } else {
                                                EzeeBooking::where("SubBookingId", $sub_booking_id)
                                                    ->update([
                                                        'RoomTypeName' => $roomTypeName,
                                                        'RoomName' => $roomName,
                                                        'TotalExtraCharge' => $totalExtraCharge,
                                                        'TotalAmountAfterTax' => $totalAmountAfterTax,
                                                    ]);
                                                if ($sub_booking_id && !$exist->folio_no) {
                                                    $this->fetchFolioForBooking($sub_booking_id, $listing->hotel_code, $listing->auth_key);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $i++;
                        }
                    }
                }
            }
        }
    }

    private function fetchFolioForBooking($subBookingId, $hotelCode, $authKey)
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

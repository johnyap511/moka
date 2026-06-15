<?php

namespace App\Http\Controllers\Admin;

use App\Booking;
use App\DataLog;
use App\Events\BookingCompleteEZEEAPIEvent;
use App\EzeeGroup;
use App\EzeeSyncLog;
use App\Http\Controllers\Controller;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Role;
use App\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mpdf\Tag\Pre;

class BookController extends Controller
{
    public function __construct()
    {
        // $this->historicalAPI();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($listingId)
    {
        $listing = Listing::find($listingId);
        // dd($listing);
        return view('admin.listing.bookCreate', compact('listing'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|max:120',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adult' => 'required|numeric',
            'infant' => 'nullable|numeric',
            'name' => 'required|string',
            'cleaning_fee' => 'required',
            'ota_fee' => 'required',
            'sst' => 'required',
            'sst_cf' => 'required',
            'source' => 'required',
        ]);

        // dd($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $start = Carbon::parse($request->check_in)->format('m');
        $end = Carbon::parse($request->check_out)->format('m');
        $new_date['date5'] = Carbon::parse($request->check_out)->subDay(1)->format('Y-m-d');
        if ($start != $end) {
            $new_date['date1'] = $request->check_in;
            $new_date['date2'] = $request->check_out;
            $new_date['date3'] = Carbon::parse($request->check_in)->format('Y-m-d');
            $new_date['date4'] = Carbon::parse($request->check_out)->format('Y-m-d');
            $is_available = Booking::where('listing_id', $request->listing_id)
                ->where('status', '!=', 1)->whereDate('check_out', '>', $request->check_in)
                ->where(function ($query) use ($request) {
                    $query
                        ->whereDate('check_in', '<', $request->check_out)
                        ->orWhereBetween('check_out', [$request->check_in, $request->check_out]);
                })
                ->get();
        } else {
            $is_available = Booking::where('listing_id', $request->listing_id)->where('status', '!=', 1)->whereDate('check_out', '>', $request->check_in)
                ->where(function ($query) use ($request) {
                    $query
                        ->whereDate('check_in', '<', $request->check_out)
                        ->orWhereBetween('check_out', [$request->check_in, $request->check_out]);
                })
                ->get();

        }
        $count = count($is_available);
        // dd($count);
        if ($count > 0) {
            return back()->with('error', 'Booking on same date is already exists! for given listing');
        }
        $data = $request->only("name", "last_name", "email", "phone");
        $bookData = $request->only("folio_no", "check_in", "check_out", "adult", "infant", 'price_night', 'cleaning_fee', 'ota_fee', 'discount_fee', 'sst', 'sst_cf', 'price', "remark", "source", "category");
        if ($bookData['check_out'] <= $bookData['check_in']) {
            return back()->with('error', 'The check out should be bigger than check in!')->withInput();
        }

        $today = date("Y-m-d");
        $books = Booking::where([['listing_id', $id], ['status', 5], ['check_out', '>=', $today]])->get();
        // $startDate = Carbon::parse($request->check_in);
        // $endDate = Carbon::parse($request->check_out);
        // $daysDifference = $startDate->diffInDays($endDate);
        foreach ($books as $book) {
            if ($book->check_in == $bookData['check_out'] && $book->check_in < $bookData['check_out'] && $book->check_out > $bookData['check_in']) {
                return back()->with('error', 'These dates are not available!')->withInput();

            }
        }
        // dd("okey");
        if (!empty($request->phone)) {
            $user = User::where('phone', $request->phone)->first();
        } else {
            $user = User::where('email', $request->email)->first();
        }

        if (empty($user)) {
            if (empty($request->name)) {
                $data['name'] = '';
            }
            $user = User::create($data);
            $role = Role::find(2);
            $user->attachRole($role);
        } else {
            $user->update($data);
        }

        $bookData['user_id'] = $user->id;
        $bookData['listing_id'] = $id;
        if (!isset($bookData['infant'])) {
            $bookData['infant'] = 0;
        }

        $listing = Listing::find($id);
        $bookingStartDate = date_create($bookData['check_in']);
        $bookingEndDate = date_create($bookData['check_out']);
        $interval = date_diff($bookingStartDate, $bookingEndDate);
        $bookData['nights'] = $interval->format('%a');
        $totaldays = $interval->format('%a');
        //        $bookData['price_night'] = 0;
        //        if(empty($request->price)){
        //            while($bookingStartDate <= $bookingEndDate){
        //                $price = ListingPrice::where([['listing_id', $id], ['date', date_format($bookingStartDate, "Y-m-d")]])->first();
        //                if(empty($price)){
        //                    $price = $listing->default_price;
        //                }else{
        //                    $price = $price->price;
        //                }
        //                $bookData['price'] = $bookData['price']+ $price;
        //                $bookingStartDate->modify('+1 day');
        //            }
        //        }else{
        //            $bookData['price'] = $request->price_night*$bookData['nights'];
        //        }

        $bookData['status'] = 5;

        if (date_format($bookingStartDate, 'Y') != date_format($bookingEndDate, 'Y') || date_format($bookingStartDate, 'm') != date_format($bookingEndDate, 'm')) {
            $ts1 = strtotime($bookData['check_in']);
            $ts2 = strtotime($bookData['check_out']);
            $year1 = date('Y', $ts1);
            $year2 = date('Y', $ts2);
            $month1 = date('m', $ts1);
            $month2 = date('m', $ts2);
            $diffM = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;
            if (date('d', $ts2) == '01') {
                $diffM = $diffM - 1;
            }
            $tempEndDate = date_format($bookingStartDate, 'Y-m-t');
            $tempEndDate = date_create($tempEndDate);
            $tempEndDate->modify('+1 day');
            $tempStartDate = $bookingStartDate;
            $single_ota = round($request->ota_fee / $totaldays, 2);
            $single_sst = round($request->sst / $totaldays, 2);
            // dd($single_ota,  $single_sst ,  $totaldays);
            for ($i = 1; $i <= $diffM; $i++) {
                if (date_format($tempEndDate, 'Y-m-d') > date_format($bookingEndDate, 'Y-m-d')) {
                    $tempEndDate = $bookingEndDate;
                }
                $intervalThis = date_diff($tempStartDate, $tempEndDate);
                $nightsThis = $intervalThis->format('%a');
                if ($i == 1) {
                    $bookData['cleaning_fee'] = $request->cleaning_fee;
                    $bookData['sst_cf'] = $request->sst_cf;
                    $bookData['discount_fee'] = $request->discount_fee;
                } else {
                    $bookData['cleaning_fee'] = 0;
                    $bookData['sst_cf'] = 0;
                    $bookData['discount_fee'] = 0;
                }

                if ($i == 1) {
                    $bookData['ota_fee'] = round($single_ota * $nightsThis, 2);
                } else {
                    $bookData['ota_fee'] = round($single_ota * $nightsThis, 2);
                }

                if ($i == 1) {
                    $bookData['sst'] = round($single_sst * $nightsThis, 2);
                } else {
                    $bookData['sst'] = round($single_sst * $nightsThis, 2);
                }
                // $bookData['sst_cf'] = round($bookData['sst_cf'] / $nightsThis, 2);
                // $bookData['sst'] = round($bookData['sst'] / $nightsThis, 2);
                // $bookData['ota_fee'] = round($bookData['ota_fee'] / $nightsThis, 2);
                // $bookData['discount_fee'] = round($bookData['discount_fee'] / $diffM, 2);
                $bookData['nights'] = $nightsThis;
                $bookData['price'] = ($bookData['price_night'] * $nightsThis) + $bookData['cleaning_fee'] - $bookData['ota_fee'] + $bookData['sst'] - $bookData['discount_fee'] + $bookData['sst_cf'];
                $bookData['check_in'] = date_format($tempStartDate, 'Y-m-d');
                $bookData['check_out'] = date_format($tempEndDate, 'Y-m-d');
                $bookData['source'] = $request->source;
                //                echo $bookData['check_in'].' _ '.$bookData['check_out'].'<br />';
                $booking = Booking::create($bookData);
                $tempStartDate->modify('first day of this month');
                $tempStartDate->modify('+1 month');

                $tempEndDate->modify('+1 month');
            }
        } else {
            // dd($bookData);
            $booking = Booking::create($bookData);
            // if($booking){
            //     dd($booking);
            // }
        }

        $bookEventData = (['book_id' => $booking->id]);
        Event::dispatch(new BookingCompleteEZEEAPIEvent($bookEventData));
        $email = $user->email;
        if (!empty($email)) {
            $data22['status'] = 5;
            $data22['name'] = $user->name;
            $data22['check_in'] = $booking->check_in;
            $data22['check_out'] = $booking->check_out;
            $data22['listing_name'] = $listing->name;
            //            Mail::to($email)->queue(new BookingStatusMail($data22));
        }

        $url = $request->url;
        if (!empty($url) && $url == 'dashboard') {
            return redirect('/admin/dashboard')->with('success', 'Booking is created successfully!');
        }
        return redirect('/admin/listing')->with('success', 'Booking is created successfully!');
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\Response
     */
    public function ezeeBookings()
    {
        $currentMonth = date('Y-m-') . '01';
        $newdate = date("Y-m-d", strtotime('-1 month', strtotime($currentMonth)));
        $books = EzeeBooking::where([['End', '>=', $newdate]])->whereIn('status', [5, 8])
            ->orderBy('id', 'desc')->get();
        $listings = Listing::where('status', 1)->get();

        // Build listing name map from linked bookings
        $bookIds = $books->whereNotNull('book_id')->pluck('book_id');
        $linkedListings = \App\Booking::whereIn('id', $bookIds)
            ->with('listing:id,name')
            ->get(['id','listing_id'])
            ->keyBy('id');

        return view('admin.listing.book.ezeeBook', compact('books', 'listings', 'linkedListings'));
    }

    public static function historicalAPI()
    {
        // echo "hi";die;
        set_time_limit(0);
        $listings = EzeeGroup::all();
        $date_current = date("Y-m-d");

        $newDate = date("Y-m-d", strtotime("-3 days"));
        $first_date_of_month = date("Y-m-01");

        $new_date_folio = date("Y-m-d", strtotime("-30 days"));

        $ezee_booking_folio = EzeeBooking::whereNotNull('book_id')->whereBetween('Start', [$new_date_folio, $date_current])->get();
        // dd($ezee_booking_folio);
        $postData_F['Request_Type'] = 'RetrieveListofBills';
        foreach ($ezee_booking_folio as $get_folio_no) {
            // echo '<pre>';
            // print_r($get_folio_no);
            $postData_F['Authentication'] = [
                'HotelCode' => 19676,
                'AuthCode' => '7181420090112972af-41e8-11ec-9',
                'BookingId' => $get_folio_no->SubBookingId,
            ];

            $pd_f['RES_Request'] = $postData_F;
            $payload_f = json_encode($pd_f);
            $notifyList = [];
            $ch_f = curl_init();
            curl_setopt(
                $ch_f,
                CURLOPT_URL,
                "https://live.ipms247.com/index.php/page/service.kioskconnectivity"
            );
            curl_setopt($ch_f, CURLOPT_POST, 1);
            curl_setopt($ch_f, CURLOPT_POSTFIELDS, $payload_f);
            curl_setopt(
                $ch_f,
                CURLOPT_HTTPHEADER,
                array('Content-Type:application/json')
            );
            curl_setopt($ch_f, CURLOPT_RETURNTRANSFER, true);
            $server_output_f = curl_exec($ch_f);
            curl_close($ch_f);
            $res_f = json_decode($server_output_f, true);
            $dataLog = DataLog::create([
                'title' => 'folio_no',
                'data' => $server_output_f,
                'related_id' => 19676,
                'status' => 'getFolio',
            ]);

            if (isset($res_f['Success']['FolioList'][0]['foliono'])) {
                Booking::where('id', $get_folio_no->book_id)
                    ->update(['server_folio_no' => $res_f['Success']['FolioList'][0]['foliono']]);
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
            <ToDate>' . $date_current . '</ToDate>
        </RES_Request>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml',
                    'Cookie: AWSALB=7rZ/rDok4zKkcbOzsmCsjFd9Wd7WF91XvvLA9j91IpkFz8SDgQ02uIarkeLrm57vOkI+foqTGxVZH1hK+XeiFoPLHbZydZMrC0wC+ncgOxLCrwoDKWBVvdeET4tk; AWSALBCORS=7rZ/rDok4zKkcbOzsmCsjFd9Wd7WF91XvvLA9j91IpkFz8SDgQ02uIarkeLrm57vOkI+foqTGxVZH1hK+XeiFoPLHbZydZMrC0wC+ncgOxLCrwoDKWBVvdeET4tk; SSID=ri5l2lighfde46pq1du9ngvoen',
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            DataLog::create(['title' => 'ezee_raw_xml', 'data' => substr($response, 0, 8000), 'related_id' => $listing->id ?? 0, 'status' => 'debug']);

            $xml = simplexml_load_string(trim($response));
            $json = json_encode($xml);
            $res = json_decode($json, true);
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
                                            $sub_booking_id = null;
                                        } else {
                                            $sub_booking_id = $reserve1['BookingTran']['SubBookingId'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TransactionId'])) {
                                            $transaction_id = null;
                                        } else {
                                            $transaction_id = $reserve1['BookingTran']['TransactionId'];
                                        }

                                        if (is_array($reserve1['BookingTran']['IsConfirmed'])) {
                                            $is_confirmed = null;
                                        } else {
                                            $is_confirmed = $reserve1['BookingTran']['IsConfirmed'];
                                        }

                                        if (is_array($reserve1['BookingTran']['RateplanName'])) {
                                            $rateplanName = null;
                                        } else {
                                            $rateplanName = $reserve1['BookingTran']['RateplanName'];
                                        }

                                        if (is_array($reserve1['BookingTran']['RoomTypeName'])) {
                                            $roomTypeName = null;
                                        } else {
                                            $roomTypeName = $reserve1['BookingTran']['RoomTypeName'];
                                        }

                                        if (isset($reserve1['BookingTran']['RoomName']) && !is_array($reserve1['BookingTran']['RoomName'])) {
                                            $roomName = $reserve1['BookingTran']['RoomName'];
                                        } else {
                                            $roomName = null;
                                        }

                                        if (is_array($reserve1['BookingTran']['Createdatetime'])) {
                                            $created_at = null;
                                        } else {
                                            $created_at = $reserve1['BookingTran']['Createdatetime'];
                                        }

                                        if (is_array($reserve1['BookingTran']['Start'])) {
                                            $start = null;
                                        } else {
                                            $start = $reserve1['BookingTran']['Start'];
                                        }

                                        if (is_array($reserve1['BookingTran']['End'])) {
                                            $end = null;
                                        } else {
                                            $end = $reserve1['BookingTran']['End'];
                                        }

                                        if (is_array($reserve1['BookingTran']['CurrencyCode'])) {
                                            $currencyCode = null;
                                        } else {
                                            $currencyCode = $reserve1['BookingTran']['CurrencyCode'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalAmountAfterTax'])) {
                                            $totalAmountAfterTax = null;
                                        } else {
                                            $totalAmountAfterTax = $reserve1['BookingTran']['TotalAmountAfterTax'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalAmountBeforeTax'])) {
                                            $totalAmountBeforeTax = null;
                                        } else {
                                            $totalAmountBeforeTax = $reserve1['BookingTran']['TotalAmountBeforeTax'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalDiscount'])) {
                                            $totalDiscount = null;
                                        } else {
                                            $totalDiscount = $reserve1['BookingTran']['TotalDiscount'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalExtraCharge'])) {
                                            $totalExtraCharge = null;
                                        } else {
                                            $totalExtraCharge = $reserve1['BookingTran']['TotalExtraCharge'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalPayment'])) {
                                            $totalPayment = null;
                                        } else {
                                            $totalPayment = $reserve1['BookingTran']['TotalPayment'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TACommision'])) {
                                            $tACommision = null;
                                        } else {
                                            $tACommision = $reserve1['BookingTran']['TACommision'];
                                        }

                                        if (is_array($reserve1['FirstName'])) {
                                            $first_name = null;
                                        } else {
                                            $first_name = $reserve1['FirstName'];
                                        }

                                        if (is_array($reserve1['LastName'])) {
                                            $last_name = null;
                                        } else {
                                            $last_name = $reserve1['LastName'];
                                        }

                                        if (is_array($reserve1['Mobile'])) {
                                            $mobile = null;
                                        } else {
                                            $mobile = $reserve1['Mobile'];
                                        }

                                        if (is_array($reserve1['Email'])) {
                                            $email = null;
                                        } else {
                                            $email = $reserve1['Email'];
                                        }

                                        if (is_array($reserve1['Country'])) {
                                            $country = null;
                                        } else {
                                            $country = $reserve1['Country'];
                                        }

                                        if (is_array($reserve1['BookingTran']['BookedBy'])) {
                                            $source = null;
                                        } else {
                                            $source = $reserve1['BookingTran']['BookedBy'];
                                        }

                                        $exist = EzeeBooking::where('SubBookingId', $sub_booking_id)->first();
                                        if (empty($exist)) {
                                            if ($sub_booking_id) {
                                                $exist = EzeeBooking::create([
                                                    'SubBookingId' => $sub_booking_id,
                                                    'TransactionId' => $transaction_id,
                                                    'IsConfirmed' => $is_confirmed,
                                                    'RateplanName' => $rateplanName,
                                                    'RoomTypeName' => $roomTypeName,
                                                    'RoomName' => $roomName,
                                                    'Start' => $start,
                                                    'End' => $end,
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
                                                    'Source' => preg_replace('/[^A-Za-z\. ]/', '', $source),
                                                    'created_at' => $created_at,
                                                ]);
                                            }
                                        } else {
                                            EzeeBooking::where("SubBookingId", $sub_booking_id)
                                                ->update(["TotalExtraCharge" => $totalExtraCharge]);
                                        }
                                    } else {
                                        foreach ($reserve1['BookingTran'] as $reserve_array_value) {

                                            if (is_array($reserve_array_value['SubBookingId'])) {
                                                $sub_booking_id = null;
                                            } else {
                                                $sub_booking_id = $reserve_array_value['SubBookingId'];
                                            }

                                            if (is_array($reserve_array_value['TransactionId'])) {
                                                $transaction_id = null;
                                            } else {
                                                $transaction_id = $reserve_array_value['TransactionId'];
                                            }

                                            if (is_array($reserve_array_value['IsConfirmed'])) {
                                                $is_confirmed = null;
                                            } else {
                                                $is_confirmed = $reserve_array_value['IsConfirmed'];
                                            }

                                            if (is_array($reserve_array_value['RateplanName'])) {
                                                $rateplanName = null;
                                            } else {
                                                $rateplanName = $reserve_array_value['RateplanName'];
                                            }

                                            if (is_array($reserve_array_value['RoomTypeName'])) {
                                                $roomTypeName = null;
                                            } else {
                                                $roomTypeName = $reserve_array_value['RoomTypeName'];
                                            }

                                            if (isset($reserve_array_value['RoomName']) && !is_array($reserve_array_value['RoomName'])) {
                                                $roomName = $reserve_array_value['RoomName'];
                                            } else {
                                                $roomName = null;
                                            }

                                            if (is_array($reserve_array_value['Start'])) {
                                                $start = null;
                                            } else {
                                                $start = $reserve_array_value['Start'];
                                            }

                                            if (is_array($reserve_array_value['End'])) {
                                                $end = null;
                                            } else {
                                                $end = $reserve_array_value['End'];
                                            }

                                            if (is_array($reserve_array_value['CurrencyCode'])) {
                                                $currencyCode = null;
                                            } else {
                                                $currencyCode = $reserve_array_value['CurrencyCode'];
                                            }

                                            if (is_array($reserve_array_value['TotalAmountAfterTax'])) {
                                                $totalAmountAfterTax = null;
                                            } else {
                                                $totalAmountAfterTax = $reserve_array_value['TotalAmountAfterTax'];
                                            }

                                            if (is_array($reserve_array_value['TotalAmountBeforeTax'])) {
                                                $totalAmountBeforeTax = null;
                                            } else {
                                                $totalAmountBeforeTax = $reserve_array_value['TotalAmountBeforeTax'];
                                            }

                                            if (is_array($reserve_array_value['TotalDiscount'])) {
                                                $totalDiscount = null;
                                            } else {
                                                $totalDiscount = $reserve_array_value['TotalDiscount'];
                                            }

                                            if (is_array($reserve_array_value['TotalExtraCharge'])) {
                                                $totalExtraCharge = null;
                                            } else {
                                                $totalExtraCharge = $reserve_array_value['TotalExtraCharge'];
                                            }

                                            if (is_array($reserve_array_value['TotalPayment'])) {
                                                $totalPayment = null;
                                            } else {
                                                $totalPayment = $reserve_array_value['TotalPayment'];
                                            }

                                            if (is_array($reserve_array_value['TACommision'])) {
                                                $tACommision = null;
                                            } else {
                                                $tACommision = $reserve_array_value['TACommision'];
                                            }

                                            if (is_array($reserve1['FirstName'])) {
                                                $first_name = null;
                                            } else {
                                                $first_name = $reserve1['FirstName'];
                                            }

                                            if (is_array($reserve1['LastName'])) {
                                                $last_name = null;
                                            } else {
                                                $last_name = $reserve1['LastName'];
                                            }

                                            if (is_array($reserve1['Mobile'])) {
                                                $mobile = null;
                                            } else {
                                                $mobile = $reserve1['Mobile'];
                                            }

                                            if (is_array($reserve1['Email'])) {
                                                $email = null;
                                            } else {
                                                $email = $reserve1['Email'];
                                            }

                                            if (is_array($reserve1['Country'])) {
                                                $country = null;
                                            } else {
                                                $country = $reserve1['Country'];
                                            }

                                            if (is_array($reserve_array_value['BookedBy'])) {
                                                $source = null;
                                            } else {
                                                $source = $reserve_array_value['BookedBy'];
                                            }

                                            if (is_array($reserve_array_value['Createdatetime'])) {
                                                $created_at = null;
                                            } else {
                                                $created_at = $reserve_array_value['Createdatetime'];
                                            }

                                            $exist = EzeeBooking::where('SubBookingId', $sub_booking_id)->first();

                                            if (empty($exist)) {
                                                if ($sub_booking_id) {

                                                    $exist = EzeeBooking::create([
                                                        'SubBookingId' => $sub_booking_id,
                                                        'TransactionId' => $transaction_id,
                                                        'IsConfirmed' => $is_confirmed,
                                                        'RateplanName' => $rateplanName,
                                                        'RoomTypeName' => $roomTypeName,
                                                        'RoomName' => $roomName,
                                                        'Start' => $start,
                                                        'End' => $end,
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
                                                        'Source' => preg_replace('/[^A-Za-z\. ]/', '', $source),
                                                        'created_at' => $created_at,
                                                    ]);
                                                }
                                            } else {
                                                EzeeBooking::where("SubBookingId", $sub_booking_id)
                                                    ->update(["TotalExtraCharge" => $totalExtraCharge]);
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

    public function ezeeBookingsUnAssigned()
    {
        $currentMonth = date('Y-m-') . '01';
        $newdate = date("Y-m-d", strtotime('-1 month', strtotime($currentMonth)));
        // $books = EzeeBooking::where([['End', '>=', $newdate]])->whereIn('status', [5])->orderBy('id','DESC')->limit(100)->get();
        $books = EzeeBooking::where([['End', '>=', $newdate]])->whereIn('status', [5])->get();
        $listings = Listing::where('status', 1)->get();
        $bookIds = $books->whereNotNull('book_id')->pluck('book_id');
        $linkedListings = \App\Booking::whereIn('id', $bookIds)->with('listing:id,name')->get(['id','listing_id'])->keyBy('id');
        return view('admin.listing.book.ezeeBook', compact('books', 'listings', 'linkedListings'));
    }

    public function ezeeBookingsAssigned()
    {
        $currentMonth = date('Y-m-') . '01';
        $newdate = date("Y-m-d", strtotime('-1 month', strtotime($currentMonth)));
        $books = EzeeBooking::where([['End', '>=', $newdate]])->whereIn('status', [8])->get();
        $listings = Listing::where('status', 1)->get();
        $bookIds = $books->whereNotNull('book_id')->pluck('book_id');
        $linkedListings = \App\Booking::whereIn('id', $bookIds)->with('listing:id,name')->get(['id','listing_id'])->keyBy('id');
        return view('admin.listing.book.ezeeBook', compact('books', 'listings', 'linkedListings'));
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\Response
     */
    public function ezeeBookingsDate($date)
    {
        $currentMonth = $date;
        $books = EzeeBooking::where([['status', 5]])->whereDate('created_at', '=', $currentMonth)->get();
        $listings = Listing::where('status', 1)->get();
        return view('admin.listing.book.ezeeBook', compact('books', 'listings'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ezeeBookingStoreEdit(Request $request, $bookId)
    {
       
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $ezee = EzeeBooking::where('id', $bookId)->first();
        if (empty($ezee)) {
            return back()->with('success', 'Invalid EZEE booking ID!');
        }

        $ezee->Start = $request->check_in ?? $ezee->Start;
        $ezee->End = $request->check_out ?? $ezee->End;
        $ezee->TotalDiscount = $request->discount_fee ?? $ezee->TotalDiscount;
        $ezee->TotalExtraCharge = $request->cleaning_fee ?? $ezee->TotalExtraCharge;



        $sst_date = Carbon::now()->format('Y-m-d');
        $sst_check = "2024-03-01";
        $start = Carbon::parse($ezee->Start)->format('m');
        $end = Carbon::parse($ezee->End)->format('m');
        $new_date['date5'] = Carbon::parse($ezee->End)->subDay(1)->format('Y-m-d');

        if ($start != $end) {
            $new_date['date1'] = $ezee->Start;
            $new_date['date2'] = $ezee->End;
            $new_date['date3'] = Carbon::parse($ezee->Start)->format('Y-m-d');
            $new_date['date4'] = Carbon::parse($ezee->End)->format('Y-m-d');
            $is_available = Booking::where('listing_id', $request->listing_id)
                ->where('status', '!=', 1)->whereDate('check_out', '>', $ezee->Start)
                ->where(function ($query) use ($ezee) {
                    $query
                        ->whereDate('check_in', '<', $ezee->End)
                        ->orWhereBetween('check_out', [$ezee->Start, $ezee->End]);
                })
                ->get();
        } else {
            $is_available = Booking::where('listing_id', $request->listing_id)
                ->where('status', '!=', 1)->whereDate('check_out', '>', $ezee->Start)
                ->where(function ($query) use ($ezee) {
                    $query
                        ->whereDate('check_in', '<', $ezee->End)
                        ->orWhereBetween('check_out', [$ezee->Start, $ezee->End]);
                })
                ->get();
        }
        $arrays = [];
        $count = count($is_available);
        if ($count > 0) {
            return back()->with('error', 'Booking on same date is already exists! for given listing');
        }
        if ($request->reassign) {
            if ($ezee->book_id) {
                Booking::where('id', $ezee->book_id)
                    ->update(['listing_id' => $request->listing_id]);
                return back()->with('success', 'EZEE booking reassigned successfully!');
            } else {
                return back()->with('error', 'You can not reassign! Because relation is missing');
            }
        }
        // server folio number
        $ezee_booking_folio = EzeeBooking::where('SubBookingId', $ezee->SubBookingId)->first();
        if ($ezee_booking_folio) {
            $postData_F['Request_Type'] = 'RetrieveListofBills';
            $listings = EzeeGroup::all();
            foreach ($listings as $listing) {
                $postData_F['Authentication'] = [
                    'HotelCode' => $listing->hotel_code,
                    'AuthCode' => $listing->auth_key,
                    'BookingId' => $ezee_booking_folio->SubBookingId,
                    'TransactionId' => $ezee_booking_folio->TransactionId,
                ];
                // dd($postData_F);
                $pd_f['RES_Request'] = $postData_F;
                $payload_f = json_encode($pd_f);

                $notifyList = [];
                $ch_f = curl_init();
                curl_setopt(
                    $ch_f,
                    CURLOPT_URL,
                    "https://live.ipms247.com/index.php/page/service.kioskconnectivity"
                );
                curl_setopt($ch_f, CURLOPT_POST, 1);
                curl_setopt($ch_f, CURLOPT_POSTFIELDS, $payload_f);
                curl_setopt(
                    $ch_f,
                    CURLOPT_HTTPHEADER,
                    array('Content-Type:application/json')
                );
                curl_setopt($ch_f, CURLOPT_RETURNTRANSFER, true);
                $server_output_f = curl_exec($ch_f);
                curl_close($ch_f);
                $res_f = json_decode($server_output_f, true);

                $dataLog = DataLog::create([
                    'title' => 'folio_no',
                    'data' => $server_output_f,
                    'related_id' => $listing->hotel_code,
                    'status' => 'getFolio',
                ]);
                $data = json_decode($dataLog['data'], true);
                // dd($data['Errors']['ErrorCode']);
                if (isset($data['Success']['FolioList'][0]['foliono']) && $data['Errors']['ErrorCode'] != 204) {
                    // dd($data['Success']['FolioList'][0]['foliono']);
                    $server = $data['Success']['FolioList'][0]['foliono'];
                    array_push($arrays, $server);
                } else {
                    $server = null;
                    array_push($arrays, $server);
                }
            }
        }
        if (count($arrays) > 0) {
            $server_folio_no = $arrays[0];
        } else {
            $server_folio_no = null;
        }

        $user = User::where('name', $ezee->FirstName)->get();
        $user_count = count($user) + 1;
        $user = User::create([
            'name' => $ezee->FirstName . $user_count,
            'last_name' => $ezee->LastName,
            'phone' => $ezee->Mobile,
            'email' => $ezee->Email,
            'ezee_tmp' => 1,
        ]);
        $role = Role::find(2);
        $user->attachRole($role);
        $userId = $user->id ?? null;
    $bookingStartDate = date_create($request->check_in);
        $bookingEndDate = date_create($request->check_out);
        $interval = date_diff($bookingStartDate, $bookingEndDate);
        $nights = $interval->format('%a');
        // echo $nights;dd("ok");

        if ($nights != 0) {
            $pricePerNight = round($ezee->TotalAmountBeforeTax / $nights, 2);
        } else {
            $pricePerNight = 0;
        }
        if (date_format($bookingStartDate, 'Y') != date_format($bookingEndDate, 'Y') || date_format($bookingStartDate, 'm') != date_format($bookingEndDate, 'm')) {
            $ts1 = strtotime($request->check_in);
            $ts2 = strtotime($request->check_out);
            $year1 = date('Y', $ts1);
            $year2 = date('Y', $ts2);
            $month1 = date('m', $ts1);
            $month2 = date('m', $ts2);
            $diffM = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;
            if (date('d', $ts2) == '01') {
                $diffM = $diffM - 1;
            }
            if ($diffM == 0) {
                $diffM = 1;
            }
            $tempEndDate = date_format($bookingStartDate, 'Y-m-t');
            $tempEndDate = date_create($tempEndDate);
            $tempEndDate->modify('+1 day');
            $tempStartDate = $bookingStartDate;

            for ($i = 1; $i <= $diffM; $i++) {
                if (date_format($tempEndDate, 'Y-m-d') > date_format($bookingEndDate, 'Y-m-d')) {
                    $tempEndDate = $bookingEndDate;
                }

                $intervalThis = date_diff($tempStartDate, $tempEndDate);
                $nightsThis = $intervalThis->format('%a');

                // $tax = round(($ezee->TotalAmountAfterTax - $ezee->TotalAmountBeforeTax) / $diffM, 2);
                // Calculate taxes and fees
        $todays = $ezee->created_at->format('Y-m-d');
        $cleaning_fee = ($i == 1) ? ($request->cleaning_fee ?? $ezee->TotalExtraCharge) : 0.00;
        $tax = 0.00;
        $sst_cf = 0.00;

        // Calculate tax
        if ($nightsThis > 0) {
            if ($todays < $sst_check) {
                $tax = round((($pricePerNight * $nightsThis) * 0.06), 2);
            } else {
                $tax = round((($pricePerNight * $nightsThis) * 0.08), 2);
            }
        }

        if ($request->source == 'Long Term Rental') {
            $tax = 0.00;
        }

        // Calculate SST for cleaning fee (only first month)
        if ($i == 1 && $cleaning_fee > 0) {
            if ($todays < $sst_check) {
                $sst_cf = round(((0.06 * $cleaning_fee)), 2);
            } else {
                $sst_cf = round(((0.08 * $cleaning_fee)), 2);
            }
        }


            
                                    // Date cutoffs matching JS constants exactly
                                    $CHECK_DATE = '2022-11-30';      // Changed to match JS exactly
                                    $CHECK_DATE_15 = '2023-02-01';
                                    $CHECK_DATE_NEW = '2023-06-17';  // Changed to match JS exactly
                                    $CHECK_DATE_NEW8 = '2023-07-01';
                                    $SEP_DATE = '2024-09-01';       // Changed to match JS exactly

                //                  
                                    // Rate constants matching JS RATES object
                                    $RATES = [
                                        'DEFAULT' => 0.20,      // Default rate 20%
                                        'BOOKING_1' => 0.18,    // Booking.com base rate
                                        'BOOKING_2' => 0.028,   // Booking.com additional rate
                                        'AIRBNB' => 0.159,      // Airbnb rate before Sep
                                        'AIRBNB_SEP' => 0.15,   // Airbnb rate after Sep
                                        'TRAVELOKA' => 0.17,    // Traveloka rate
                                        'WALK_IN' => 0.12,      // Walk-in rate 12%
                                        'WALK_IN8' => 0.08,     // Updated Walk-in rate 8%
                                        'EXPEDIA' => 0.15,      // Expedia rate
                                        'CTRIP' => 0.15         // CTrip rate
                                    ];

                                    // Calculate base values exactly like JS
                                    $ota_cal = ($pricePerNight * $nights) + $cleaning_fee;
                                    $ota_cal1 = $ota_cal + $tax + $sst_cf;
                                    $ota_cal2 = $ota_cal;

                                    //  $ota_cal = (($pricePerNight * $nightsThis));
                                    //  $ota_cal1 = (($pricePerNight * $nightsThis)) + $cleaning_fee;
                                    //  $ota_cal2 = (($pricePerNight * $nightsThis)) + $cleaning_fee + $tax + $sst_cf;
                                    if (in_array($request->source, ['Walk-in', 'Walk In', 'PMS', 'Website'])) {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE) && !$date > new DateTime($CHECK_DATE_15)) {
                                            // Period: after 2022-11-30 but not after 2023-02-01
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE_15) && $date < new DateTime($CHECK_DATE_NEW8)) {
                                            // Period: after 2023-02-01 but before 2023-07-01
                                            $ota = floor(($RATES['WALK_IN'] * $ota_cal) * 100) / 100;
                                        }
                                         elseif ($date > new DateTime($CHECK_DATE_NEW8) || !$date < new DateTime($CHECK_DATE_NEW8)) {
                                            // Period: after 2023-07-01 or not before 2023-07-01 (matching JS isAfter || !isBefore)
                                            $ota = floor(($RATES['WALK_IN8'] * $ota_cal2) * 100) / 100;
                                        }
                                         else {
                                            // Fallback: match JS formula exactly
                                            $base = $ezee->TotalAmountAfterTax + $cleaning_fee - $tax;
                                            $ota = floor((0.20 * $base) * 100) / 100;
                                        }
                                    } elseif ($request->source == 'Airbnb') {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE) && $date < new DateTime($CHECK_DATE_NEW)) {
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE_NEW) && $date < new DateTime($SEP_DATE)) {
                                            $ota = floor(($RATES['AIRBNB'] * $ota_cal) * 100) / 100;
                                        } elseif ($date >= new DateTime($SEP_DATE)) {
                                            $ota = floor(($RATES['AIRBNB_SEP'] * $ota_cal1) * 100) / 100;
                                        } else {
                                            $ota = floor(($RATES['AIRBNB'] * $ota_cal) * 100) / 100;
                                        }
                                   
                                    } elseif (in_array($request->source, ['Booking.com', 'Booking'])) {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE) && $date < new DateTime($CHECK_DATE_NEW)) {
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE_NEW)) {
                                            $ota1 = floor(($RATES['BOOKING_2'] * $ota_cal1) * 100) / 100;
                                            $ota2 = floor(($RATES['BOOKING_1'] * $ota_cal2) * 100) / 100;
                                            $ota = floor(($ota1 + $ota2) * 100) / 100;
                                        } else {
                                            $ota = floor((0.205 * $ota_cal) * 100) / 100;
                                        }
                                    } elseif ($request->source == 'Agoda') {
                                        $ota = 0; // Agoda shows 0 OTA fee in JS
                                    } elseif ($request->source == 'Traveloka') {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE) && $date < new DateTime($CHECK_DATE_NEW)) {
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE_NEW)) {
                                            $ota = floor(($RATES['TRAVELOKA'] * $ota_cal1) * 100) / 100;
                                        } else {
                                            $ota = floor(0.18 * $ota_cal * 100) / 100;
                                        }
                                    } elseif (in_array($request->source, ['Trip.com', 'CTrip.com', 'Ctrip.com', 'CTrip', 'Ctrip'])) {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE) && $date < new DateTime($CHECK_DATE_NEW)) {
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE_NEW)) {
                                            $ota = 0;
                                        } else {
                                            $ota = floor(($RATES['CTRIP'] * $ota_cal) * 100) / 100;
                                        }
                                    } elseif ($request->source == 'Expedia') {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE_NEW)) {
                                            // Period: after new check date
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal1) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE)) {
                                            // Period: after check date
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } else {
                                            // Default Expedia rate
                                            $ota = floor(($RATES['EXPEDIA'] * $ota_cal) * 100) / 100;
                                        }
                                    } elseif (in_array($request->source, ['Long Term Rental', 'Tiket.com', 'owner', 'Owner', 'Agoda'])) {
                                        // These sources have no OTA fee
                                        $ota = 0;
                                    } else {
                                        $date = new DateTime($todays);
                                        if ($date > new DateTime($CHECK_DATE) && $date < new DateTime($CHECK_DATE_NEW)) {
                                            // Period: after check date but before new check date
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
                                        } elseif ($date > new DateTime($CHECK_DATE_NEW)) {
                                            // Period: after new check date
                                            $ota = floor(($RATES['DEFAULT'] * $ota_cal1) * 100) / 100;
                                        } else {
                                            // Default 10% rate
                                            $ota = floor((0.1 * $ota_cal) * 100) / 100;
                                        }
                                 }
                                 
                                              $discount = ($i == 1) ? ($request->discount_fee ?? 0.00) : 0.00;
                                 $total = ($pricePerNight * $nightsThis) + $cleaning_fee + $tax + $sst_cf - $discount;  


                $folioNo = $request->folio_no ?? 'FN' . substr($ezee->TransactionId, -4) ;
                $otaText = '';
                if (Str::contains($request->source, 'Booking')) {
                    $otaText = 'Booking.com';
                } else if ($request->source == 'PMS' || $request->source == 'Walk-in' || $request->source == 'Walk In') {
                    $otaText = 'Website';
                } else if (Str::contains($request->source, 'CTrip')) {
                    $otaText = 'CTrip';
                } else if (Str::contains($request->source, 'Traveloka')) {
                    $otaText = 'Traveloka';
                } else {
                    $otaText = $request->source ?? '';
                }
                $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);
// dd($request->all());

                $bookingData = [
                    'listing_id' => $request->listing_id,
                    'user_id' => $userId,
                    'server_folio_no' => $server_folio_no,
                    'folio_no' => $folioNo,
                    'check_in' => date_format($tempStartDate, 'Y-m-d'),
                    'check_out' => date_format($tempEndDate, 'Y-m-d'),
                    'adult' => $request->adult ?? 0,
                    'infant' => $request->infant ?? 0,
                    'nights' => $nightsThis,
                    'price_night' => $pricePerNight,
                    'cleaning_fee' => $cleaning_fee,
                    'ota_fee' =>  $ota ?? $request->ota_fee,
                    'sst' =>  $tax ?? 0.00,
                    'sst_cf' =>  $sst_cf ?? 0.00,
                    'price' =>  $total ?? 0.00,
                    'source' =>  $otaText ?? $request->source,
                    'status' => 5,
                    'tourism_tax' => $tax ?? 0.00,
                    'discount_fee' => $request->discount_fee ?? $ezee->TotalDiscount,
                    'remark' => $request->remark ?? 'Imported from EZEE Booking ID/Source: ' . $ezee->id.'/'.$request->source,
                ];
//                 echo "<pre>";
// print_r($bookingData);
// echo "</pre>";
               $book = Booking::create($bookingData);
                $tempStartDate->modify('first day of this month');
                $tempStartDate->modify('+1 month');
                $tempEndDate->modify('+1 month');
            }
            // dd('Stopped for debug');
        } else {
           
            // $cleaning_fee = $ezee->TotalExtraCharge;
           

            // $total = ($pricePerNight * $nights) + $ezee->TotalExtraCharge + $tax + $sst_cf - $ezee->TotalDiscount;
            // if ($ezee->Start === $ezee->End) {
            //     $end_date = \Carbon\Carbon::parse($ezee->End)->addDays(1)->format('Y-m-d');
            // } else {
            //     $end_date = $ezee->End;
            // }
            // // dd($total);
            // $folioNo = 'FN' . substr($ezee->TransactionId, -4);
            $otaText = '';
            if (Str::contains($ezee->Source, 'Booking')) {
                $otaText = 'Booking.com';
            } else if ($ezee->Source == 'PMS' || $ezee->Source == 'Walk-in' || $ezee->Source == 'Walk In') {
                $otaText = 'Website';
            } else if (Str::contains($ezee->Source, 'CTrip')) {
                $otaText = 'CTrip';
            } else if (Str::contains($ezee->Source, 'Traveloka')) {
                $otaText = 'Traveloka';
            } else {
                $otaText = $ezee->Source ?? '';
            }
            $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);
             if ($request->check_in === $request->check_out) {
                $end_date = \Carbon\Carbon::parse($request->check_out)->addDays(1)->format('Y-m-d');
            } else {
                $end_date = $ezee->End;
            }
         $book = Booking::create([
                'listing_id' => $request->listing_id,
                'server_folio_no' => $server_folio_no,
                'user_id' => $userId,
                'folio_no' => $request->folio_no ?? 'FN' . substr($ezee->TransactionId, -4),
                'check_in' => $request->check_in,
                'check_out' => $end_date,
                'adult' => $request->adult ?? 2,
                'infant' => $request->infant ?? 0,
                'nights' => $nights,
                'price_night' => $pricePerNight,
                'cleaning_fee' => $request->cleaning_fee,
                'ota_fee' => $request->ota_fee ,
                'sst' => $request->sst  ,
                'sst_cf' => $request->sst_cf ,
                'price' => $request->price ,
                'source' => $request->source ?? $otaText ,
                'status' => 8,
                'tourism_tax' => $request->sst ,
                'discount_fee' => $request->discount_fee,
                'remark' => $request->remark ?? $ezee->Source,
            ]);
        }

        $bookingId = $book->id ? $book->id : '';
        // $ezee->update(['book_id'=>$bookingId, 'status'=>8]);

        EzeeBooking::where('id', $ezee->id)
            ->update([
                'book_id' => $bookingId, 
                'status' => 8,
                'Start' => $request->check_in,
                'End' => $request->check_out,
                'TotalExtraCharge' => $request->cleaning_fee ?? $ezee->TotalExtraCharge,
                'TotalDiscount' => $request->discount_fee ?? $ezee->TotalDiscount,
                'TotalAmountBeforeTax' => round(($request->price_night * $nights),2),
                'TotalAmountAfterTax' => round(($request->price_night * $nights) + $request->sst ,2),
                'TotalPayment' => round(($request->price_night * $nights) + $request->cleaning_fee +  $request->sst + $request->sst_cf - $request->discount_fee,2),
            ]);
        return back()->with('success', 'EZEE booking assigned successfully!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ezeeBookingStore(Request $request, $bookId)
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $ezee = EzeeBooking::where('id', $bookId)->first();

        if (empty($ezee)) {
            return back()->with('success', 'Invalid EZEE booking ID!');
        }
        $sst_date = Carbon::now()->format('Y-m-d');
        $sst_check = "2024-03-01";
        $start = Carbon::parse($ezee->Start)->format('m');
        $end = Carbon::parse($ezee->End)->format('m');
        $new_date['date5'] = Carbon::parse($ezee->End)->subDay(1)->format('Y-m-d');

         if ($start != $end) {
            $new_date['date1'] = $ezee->Start;
            $new_date['date2'] = $ezee->End;
            $new_date['date3'] = Carbon::parse($ezee->Start)->format('Y-m-d');
            $new_date['date4'] = Carbon::parse($ezee->End)->format('Y-m-d');
            $is_available = Booking::where('listing_id', $request->listing_id)
                ->where('status', '!=', 1)->whereDate('check_out', '>', $ezee->Start)
                ->where(function ($query) use ($ezee) {
                    $query
                        ->whereDate('check_in', '<', $ezee->End)
                        ->orWhereBetween('check_out', [$ezee->Start, $ezee->End]);
                })
                ->get();
        } else {
            $is_available = Booking::where('listing_id', $request->listing_id)
                ->where('status', '!=', 1)->whereDate('check_out', '>', $ezee->Start)
                ->where(function ($query) use ($ezee) {
                    $query
                        ->whereDate('check_in', '<', $ezee->End)
                        ->orWhereBetween('check_out', [$ezee->Start, $ezee->End]);
                })
                ->get();
        }
        // dd($is_available);
        if ($ezee->Start == $new_date['date5']) {
            $is_check = Booking::where('listing_id', $request->listing_id)
                ->where('status', '!=', 1)->whereDate('check_in', $ezee->Start)->whereDate('check_out', $ezee->End)
                ->count();
            if ($is_check == 0) {
                $is_available = [];
            }
        }
        // dd($request->all());
        $arrays = [];
        $count = count($is_available);
        if ($count > 0) {
            return back()->with('error', 'Booking on same date is already exists! for given listing');
        }
        if ($request->reassign) {
            if ($ezee->book_id) {
                Booking::where('id', $ezee->book_id)
                    ->update(['listing_id' => $request->listing_id]);
                return back()->with('success', 'EZEE booking reassigned successfully!');
            } else {
                return back()->with('error', 'You can not reassign! Because relation is missing');
            }
        }
        // server folio number
        $ezee_booking_folio = EzeeBooking::where('SubBookingId', $ezee->SubBookingId)->first();
        if ($ezee_booking_folio) {
            $postData_F['Request_Type'] = 'RetrieveListofBills';
            $listings = EzeeGroup::all();
            foreach ($listings as $listing) {
                $postData_F['Authentication'] = [
                    'HotelCode' => $listing->hotel_code,
                    'AuthCode' => $listing->auth_key,
                    'BookingId' => $ezee_booking_folio->SubBookingId,
                    'TransactionId' => $ezee_booking_folio->TransactionId,
                ];
                // dd($postData_F);
                $pd_f['RES_Request'] = $postData_F;
                $payload_f = json_encode($pd_f);

                $notifyList = [];
                $ch_f = curl_init();
                curl_setopt(
                    $ch_f,
                    CURLOPT_URL,
                    "https://live.ipms247.com/index.php/page/service.kioskconnectivity"
                );
                curl_setopt($ch_f, CURLOPT_POST, 1);
                curl_setopt($ch_f, CURLOPT_POSTFIELDS, $payload_f);
                curl_setopt(
                    $ch_f,
                    CURLOPT_HTTPHEADER,
                    array('Content-Type:application/json')
                );
                curl_setopt($ch_f, CURLOPT_RETURNTRANSFER, true);
                $server_output_f = curl_exec($ch_f);
                curl_close($ch_f);
                $res_f = json_decode($server_output_f, true);

                $dataLog = DataLog::create([
                    'title' => 'folio_no',
                    'data' => $server_output_f,
                    'related_id' => $listing->hotel_code,
                    'status' => 'getFolio',
                ]);
                $data = json_decode($dataLog['data'], true);
                // dd($data['Errors']['ErrorCode']);
                if (isset($data['Success']['FolioList'][0]['foliono']) && $data['Errors']['ErrorCode'] != 204) {
                    // dd($data['Success']['FolioList'][0]['foliono']);
                    $server = $data['Success']['FolioList'][0]['foliono'];
                    array_push($arrays, $server);
                } else {
                    $server = null;
                    array_push($arrays, $server);
                }
            }
        }
        if (count($arrays) > 0) {
            $server_folio_no = $arrays[0];
        } else {
            $server_folio_no = null;
        }

        $user = User::where('name', $ezee->FirstName)->get();
        $user_count = count($user) + 1;
        $user = User::create([
            'name' => $ezee->FirstName . $user_count, 'last_name' => $ezee->LastName, 'phone' => $ezee->Mobile,
            'email' => $ezee->Email, 'ezee_tmp' => 1,
        ]);
        $role = Role::find(2);
        $user->attachRole($role);
        // }
        $userId = $user->id ?? null;
        $bookingStartDate = date_create($ezee->Start);
        $bookingEndDate = date_create($ezee->End);
        $interval = date_diff($bookingStartDate, $bookingEndDate);
        $nights = $interval->format('%a');
        // echo $nights;dd("ok");

        if ($nights != 0) {
            $pricePerNight = round($ezee->TotalAmountBeforeTax / $nights, 2);
        } else {
            $pricePerNight = 0;
        }
        if (date_format($bookingStartDate, 'Y') != date_format($bookingEndDate, 'Y') || date_format($bookingStartDate, 'm') != date_format($bookingEndDate, 'm')) {
            $ts1 = strtotime($ezee->Start);
            $ts2 = strtotime($ezee->End);
            $year1 = date('Y', $ts1);
            $year2 = date('Y', $ts2);
            $month1 = date('m', $ts1);
            $month2 = date('m', $ts2);
            $diffM = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;
            if (date('d', $ts2) == '01') {
                $diffM = $diffM - 1;
            }
            if ($diffM == 0) {
                $diffM = 1;
            }
            $tempEndDate = date_format($bookingStartDate, 'Y-m-t');
            $tempEndDate = date_create($tempEndDate);
            $tempEndDate->modify('+1 day');
            $tempStartDate = $bookingStartDate;

            for ($i = 1; $i <= $diffM; $i++) {
                if (date_format($tempEndDate, 'Y-m-d') > date_format($bookingEndDate, 'Y-m-d')) {
                    $tempEndDate = $bookingEndDate;
                }

                $intervalThis = date_diff($tempStartDate, $tempEndDate);
                $nightsThis = $intervalThis->format('%a');

                // $tax = round(($ezee->TotalAmountAfterTax - $ezee->TotalAmountBeforeTax) / $diffM, 2);
                $todays = $ezee->created_at->format('Y-m-d');
                if ($ezee->TotalAmountAfterTax != $ezee->TotalAmountBeforeTax) {
                    if ($todays < $sst_check) {
                        $tax = round((($pricePerNight * $nightsThis) * 0.06), 2);
                    } else {
                        $tax = round((($pricePerNight * $nightsThis) * 0.08), 2);
                    }
                } else {
                    $tax = 0.00;
                }
                if ($i == 1) {
                    $cleaning_fee = $ezee->TotalExtraCharge;
                } else {
                    $cleaning_fee = 0.00;
                }
                if ($i == 1) {
                    $sst_cf = round(((0.08 * $ezee->TotalExtraCharge)), 2);
                } else {
                    $sst_cf = 0.00;
                }

              // Date constants
$CHECK_DATE = '2022-11-30';
$CHECK_DATE_15 = '2023-02-01';
$CHECK_DATE_NEW = '2023-06-17';
$CHECK_DATE_NEW8 = '2023-07-01';
$SEP_DATE = '2024-09-01';

// Rate constants
$RATES = [
    'DEFAULT' => 0.20,      // Default rate 20%
    'BOOKING_1' => 0.18,    // Booking.com base rate
    'BOOKING_2' => 0.028,   // Booking.com additional rate
    'AIRBNB' => 0.159,      // Airbnb rate before Sep
    'AIRBNB_SEP' => 0.15,   // Airbnb rate after Sep
    'TRAVELOKA' => 0.17,    // Traveloka rate
    'WALK_IN' => 0.12,      // Walk-in rate 12%
    'WALK_IN8' => 0.08,     // Updated Walk-in rate 8%
    'EXPEDIA' => 0.15,      // Expedia rate
    'CTRIP' => 0.15         // CTrip rate
];

// Calculate base values
$ota_cal = ($pricePerNight * $nights) + $ezee->TotalExtraCharge;
$ota_cal1 = $ota_cal + $tax + $sst_cf;
$ota_cal2 = $ota_cal;

// Get booking date
$bookingDate = new DateTime($ezee->created_at->format('Y-m-d'));

// Handle different booking sources
$source = trim($ezee->Source);
if (in_array($source, ['Walk-in', 'Walk In', 'PMS', 'Website'])) {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate <= new DateTime($CHECK_DATE_15)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate > new DateTime($CHECK_DATE_15) && $bookingDate < new DateTime($CHECK_DATE_NEW8)) {
        $ota = floor(($RATES['WALK_IN'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW8)) {
        $ota = floor(($RATES['WALK_IN8'] * $ota_cal2) * 100) / 100;
    } else {
        $base = $ezee->TotalAmountAfterTax + $ezee->TotalExtraCharge - $tax;
        $ota = floor((0.18 * $base) * 100) / 100;
    }
} elseif ($source === 'Airbnb') {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW) && $bookingDate < new DateTime($SEP_DATE)) {
        $ota = floor(($RATES['AIRBNB'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($SEP_DATE)) {
        $ota = floor(($RATES['AIRBNB_SEP'] * $ota_cal1) * 100) / 100;
    } else {
        $ota = floor(($RATES['AIRBNB'] * $ota_cal) * 100) / 100;
    }
} elseif (in_array($source, ['Booking.com', 'Booking'])) {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota1 = floor(($RATES['BOOKING_2'] * $ota_cal1) * 100) / 100;
        $ota2 = floor(($RATES['BOOKING_1'] * $ota_cal2) * 100) / 100;
        $ota = floor(($ota1 + $ota2) * 100) / 100;
    } else {
        $ota = floor((0.205 * $ota_cal) * 100) / 100;
    }
} elseif ($source === 'Agoda') {
    $ota = 0;
} elseif ($source === 'Traveloka') {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['TRAVELOKA'] * $ota_cal1) * 100) / 100;
    } else {
        $ota = floor((0.18 * $ota_cal) * 100) / 100;
    }
} elseif (in_array($source, ['Trip.com', 'CTrip.com', 'Ctrip.com', 'CTrip', 'Ctrip'])) {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = 0;
    } else {
        $ota = floor(($RATES['CTRIP'] * $ota_cal) * 100) / 100;
    }
} elseif ($source === 'Expedia') {
    if ($bookingDate > new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal1) * 100) / 100;
    } elseif ($bookingDate > new DateTime($CHECK_DATE)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } else {
        $ota = floor(($RATES['EXPEDIA'] * $ota_cal) * 100) / 100;
    }
} elseif (in_array($source, ['Long Term Rental', 'Tiket.com', 'owner', 'Owner'])) {
    $ota = 0;
} else {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal1) * 100) / 100;
    } else {
        $ota = floor((0.1 * $ota_cal) * 100) / 100;
    }
}

// Calculate total
$total = ($pricePerNight * $nights) + $cleaning_fee + $tax + $sst_cf - $ezee->TotalDiscount;

                $folioNo = 'FN' . substr($ezee->TransactionId, -4);
                $otaText = '';
                if (Str::contains($ezee->Source, 'Booking')) {
                    $otaText = 'Booking.com';
                } else if ($ezee->Source == 'PMS' || $ezee->Source == 'Walk-in' || $ezee->Source == 'Walk In') {
                    $otaText = 'Website';
                } else if (Str::contains($ezee->Source, 'CTrip')) {
                    $otaText = 'CTrip';
                } else {
                    $otaText = $ezee->Source ?? '';
                }
                $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);

                $bookingData = [
                    'listing_id' => $request->listing_id, 'user_id' => $userId, 'server_folio_no' => $server_folio_no, 'folio_no' => $folioNo, 'check_in' => date_format($tempStartDate, 'Y-m-d'),
                    'check_out' => date_format($tempEndDate, 'Y-m-d'), 'adult' => 2, 'infant' => 0,
                    'nights' => $nightsThis, 'price_night' => $pricePerNight, 'cleaning_fee' => $cleaning_fee, 'ota_fee' => $ota, 'sst' => $tax, 'sst_cf' => $sst_cf,
                    'price' => $total, 'source' => $otaText, 'status' => 5, 'tourism_tax' => $tax, 'discount_fee' => $ezee->TotalDiscount, 'remark' => $ezee->Source,
                ];

                $book = Booking::create($bookingData);
                $tempStartDate->modify('first day of this month');
                $tempStartDate->modify('+1 month');
                $tempEndDate->modify('+1 month');
            }
        } else {
            $todays = $ezee->created_at->format('Y-m-d');
            $chackdate = '2022-11-30';
            $chackdate15 = '2023-02-01';
            $chackdatenew = '2023-06-17';
            $chackdatenew8 = '2023-07-01';
            // $cal_rate =  (($pricePerNight / $nights));
            if ($nights != 0) {
                $cal_rate = (($pricePerNight / $nights));
            } else {
                $cal_rate = 0;
            }

            $sst_cf = round(($ezee->TotalExtraCharge * 0.08), 2);
            if ($todays < $sst_check) {
                $tax = $ezee->TotalAmountAfterTax - $ezee->TotalAmountBeforeTax;
            } else {
                $tax = round(($ezee->TotalAmountBeforeTax * 0.08), 2);
            }
       // Date constants
$CHECK_DATE = '2022-11-30';
$CHECK_DATE_15 = '2023-02-01';
$CHECK_DATE_NEW = '2023-06-17';
$CHECK_DATE_NEW8 = '2023-07-01';
$SEP_DATE = '2024-09-01';

// Rate constants
$RATES = [
    'DEFAULT' => 0.20,      // Default rate 20%
    'BOOKING_1' => 0.18,    // Booking.com base rate
    'BOOKING_2' => 0.028,   // Booking.com additional rate
    'AIRBNB' => 0.159,      // Airbnb rate
    'TRAVELOKA' => 0.17,    // Traveloka rate
    'WALK_IN' => 0.12,      // Walk-in rate 12%
    'WALK_IN8' => 0.08,     // Updated Walk-in rate 8%
    'EXPEDIA' => 0.15,      // Expedia rate
    'CTRIP' => 0.15         // CTrip rate
];

// Calculate base values
$ota_cal = ($pricePerNight * $nights) + $ezee->TotalExtraCharge;
$ota_cal1 = $ota_cal + $tax + $sst_cf;
$ota_cal2 = $ota_cal;

// Get booking date
$bookingDate = new DateTime($ezee->created_at->format('Y-m-d'));

// Handle different booking sources
$source = trim($ezee->Source);
if (in_array($source, ['Walk-in', 'Walk In', 'PMS', 'Website'])) {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate <= new DateTime($CHECK_DATE_15)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate > new DateTime($CHECK_DATE_15) && $bookingDate < new DateTime($CHECK_DATE_NEW8)) {
        $ota = floor(($RATES['WALK_IN'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW8)) {
        $ota = floor(($RATES['WALK_IN8'] * $ota_cal1) * 100) / 100;
    } else {
        $ota = (($pricePerNight * $nights) + $ezee->TotalExtraCharge) * 0.1;
    }
} elseif ($source === 'Airbnb') {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['AIRBNB'] * $ota_cal1) * 100) / 100;
    } else {
        $ota = ($ezee->TotalAmountBeforeTax + $ezee->TotalExtraCharge) * 0.159;
    }
} elseif (in_array($source, ['Booking.com', 'Booking'])) {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota1 = floor(($RATES['BOOKING_1'] * $ota_cal1) * 100) / 100;
        $ota2 = floor(($RATES['BOOKING_2'] * $ota_cal2) * 100) / 100;
        $ota = floor(($ota1 + $ota2) * 100) / 100;
    } else {
        $base = ($pricePerNight * $nights) + $ezee->TotalExtraCharge;
        $ota = $base * 0.18 + $base * 0.025 + (($base * 0.18 + $base * 0.025) * 0.06);
    }
} elseif ($source === 'Agoda') {
    $ota = 0;
} elseif ($source === 'Traveloka') {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['TRAVELOKA'] * $ota_cal2) * 100) / 100;
    } else {
        $ota = (($pricePerNight * $nights) + $ezee->TotalExtraCharge) * 0.18;
    }
} elseif (in_array($source, ['Trip.com', 'CTrip.com', 'Ctrip.com', 'CTrip', 'Ctrip'])) {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = 0;
    } else {
        $ota = floor(($RATES['CTRIP'] * $ota_cal) * 100) / 100;
    }
} elseif ($source === 'Expedia') {
    if ($bookingDate > new DateTime($CHECK_DATE) && $bookingDate < new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } elseif ($bookingDate >= new DateTime($CHECK_DATE_NEW)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal2) * 100) / 100;
    } else {
        $ota = 0;
    }
} elseif ($source === 'Long Term Rental') {
    $ota = 0;
} else {
    if ($bookingDate > new DateTime($CHECK_DATE)) {
        $ota = floor(($RATES['DEFAULT'] * $ota_cal) * 100) / 100;
    } else {
        $ota = (($pricePerNight * $nights) + $ezee->TotalExtraCharge) * 0.1;
    }
}

// Calculate total (simplified since all conditions were the same)
$total = ($pricePerNight * $nights) + $ezee->TotalExtraCharge + $tax + $sst_cf - $ezee->TotalDiscount;
            if ($ezee->Start === $ezee->End) {
                $end_date = \Carbon\Carbon::parse($ezee->End)->addDays(1)->format('Y-m-d');
            } else {
                $end_date = $ezee->End;
            }
            // dd($total);
            $folioNo = 'FN' . substr($ezee->TransactionId, -4);
            $otaText = '';
            if (Str::contains($ezee->Source, 'Booking')) {
                $otaText = 'Booking.com';
            } else if ($ezee->Source == 'PMS' || $ezee->Source == 'Walk-in' || $ezee->Source == 'Walk In') {
                $otaText = 'Website';
            } else if (Str::contains($ezee->Source, 'CTrip')) {
                $otaText = 'CTrip';
            } else {
                $otaText = $ezee->Source ?? '';
            }
            $otaText = preg_replace('/[^A-Za-z\. ]/', '', $otaText);

            $book = Booking::create([
                'listing_id' => $request->listing_id, 'server_folio_no' => $server_folio_no, 'user_id' => $userId, 'folio_no' => $folioNo, 'check_in' => $ezee->Start, 'check_out' => $end_date, 'adult' => 2, 'infant' => 0,
                'nights' => $nights, 'price_night' => $pricePerNight, 'cleaning_fee' => $ezee->TotalExtraCharge, 'ota_fee' => $ota, 'sst' => $tax, 'sst_cf' => $sst_cf,
                'price' => $total, 'source' => $otaText, 'status' => 5, 'tourism_tax' => $tax, 'discount_fee' => $ezee->TotalDiscount, 'remark' => $ezee->Source,
            ]);
        }

        $bookingId = $book->id ? $book->id : '';
        // $ezee->update(['book_id'=>$bookingId, 'status'=>8]);

        EzeeBooking::where('id', $ezee->id)
            ->update(['book_id' => $bookingId, 'status' => 8]);
        return back()->with('success', 'EZEE booking assigned successfully!');
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ezeeBookingDelete(Request $request, $bookId)
    {
        $ezee = EzeeBooking::where('id', $bookId)->first();
        $ezee->update(['status' => 1]);
        return back()->with('success', 'EZEE booking deleted successfully!');
    }

    public function ezeeRemoveDuplicates(Request $request)
    {
        try {
            $removed   = 0;
            $diagnosis = [];

            // ── Step 1: find IDs to delete grouped by SubBookingId ────────────
            // For each SubBookingId with >1 active record, keep the assigned one
            // (highest book_id) or, if none assigned, the highest id (most recent).
            // Only delete records that are unassigned (book_id NULL or 0).
            $bySubId = DB::select("
                SELECT e.id
                FROM ezee_bookings e
                INNER JOIN (
                    SELECT SubBookingId,
                           COALESCE(
                               MAX(CASE WHEN book_id IS NOT NULL AND book_id > 0 THEN id END),
                               MAX(id)
                           ) AS keep_id
                    FROM ezee_bookings
                    WHERE status IN (5, 8)
                      AND SubBookingId IS NOT NULL
                      AND SubBookingId != ''
                    GROUP BY SubBookingId
                    HAVING COUNT(*) > 1
                ) k ON e.SubBookingId = k.SubBookingId
                     AND e.id != k.keep_id
                WHERE e.status IN (5, 8)
                  AND (e.book_id IS NULL OR e.book_id = 0)
                LIMIT 300
            ");

            $diagnosis[] = 'SubBookingId pass: ' . count($bySubId) . ' rows to delete';

            foreach ($bySubId as $row) {
                DB::table('ezee_bookings')->where('id', $row->id)->update(['status' => 1]);
                $removed++;
            }

            // ── Step 2: find IDs to delete grouped by name + dates + amount ──
            // Catches records that may have different SubBookingIds but are the
            // same real-world booking (same guest, same stay, same amount).
            $byName = DB::select("
                SELECT e.id
                FROM ezee_bookings e
                INNER JOIN (
                    SELECT FirstName, LastName, `Start`, `End`, TotalAmountAfterTax,
                           COALESCE(
                               MAX(CASE WHEN book_id IS NOT NULL AND book_id > 0 THEN id END),
                               MAX(id)
                           ) AS keep_id
                    FROM ezee_bookings
                    WHERE status IN (5, 8)
                    GROUP BY FirstName, LastName, `Start`, `End`, TotalAmountAfterTax
                    HAVING COUNT(*) > 1
                ) k ON (e.FirstName <=> k.FirstName)
                     AND (e.LastName <=> k.LastName)
                     AND (e.`Start` <=> k.`Start`)
                     AND (e.`End` <=> k.`End`)
                     AND (e.TotalAmountAfterTax <=> k.TotalAmountAfterTax)
                     AND e.id != k.keep_id
                WHERE e.status IN (5, 8)
                  AND (e.book_id IS NULL OR e.book_id = 0)
                LIMIT 300
            ");

            $diagnosis[] = 'Name/date pass: ' . count($byName) . ' rows to delete';

            foreach ($byName as $row) {
                DB::table('ezee_bookings')->where('id', $row->id)->update(['status' => 1]);
                $removed++;
            }

            // ── Count remaining duplicate groups ──────────────────────────────
            $stillLeft = DB::selectOne("
                SELECT COUNT(*) AS cnt FROM (
                    SELECT 1 FROM ezee_bookings
                    WHERE status IN (5, 8)
                    GROUP BY FirstName, LastName, `Start`, `End`, TotalAmountAfterTax
                    HAVING COUNT(*) > 1
                ) t
            ")->cnt ?? 0;

            $msg = implode(' | ', $diagnosis) . ' — Removed ' . $removed . ' duplicate(s).';
            if ($stillLeft > 0) {
                $msg .= " {$stillLeft} group(s) still remain — click again.";
            } else {
                $msg .= ' All duplicates cleared!';
            }

            return back()->with('success', $msg);

        } catch (\Throwable $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function ezeeBookingsReports()
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();

        $get_all_bookings = DB::table('ezee_bookings')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$from, $to])
        // ->groupBy(substr('TransactionId'), 1, 5)
            ->groupBy('date')
            ->orderByRaw('date DESC')
            ->get();

        // $get_all_bookings = DB::table('ezee_bookings')
        // ->select(DB::raw('count(*) as total'),DB::raw('substr(TransactionId, 1, 5) as property_id') )
        // ->whereBetween('created_at', [$from, $to])
        // ->groupBy(DB::raw('substr(TransactionId, 1, 5)'))
        // ->get();

        // dd($get_all_bookings);
        return view('admin.listing.book.ezeeBookReport', compact('get_all_bookings'));
    }

    public function uploadBookings()
    {
        // dd("Hello");
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now();

        $get_all_bookings = DB::table('ezee_bookings')
            ->select(DB::raw('substr(TransactionId, 1, 5) as property_id'), DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('substr(TransactionId, 1, 5)'))
        // ->groupBy('date')
        // ->orderByRaw('date DESC')
            ->get();
        // dd($get_all_bookings);
        return view('admin.listing.book.upload_ezee_bookings', compact('get_all_bookings'));
    }

    public function uploadBookingData(Request $request)
    {
        $upload = $request->file('file');
        $filepath = $upload->getRealPath();

        //open and read
        $file = fopen($filepath, 'r');
        $header = fgetcsv($file);

        $listingMissed = [];
        $escapedHeader = [];

        $ezeeBookingArray = [];
        // // dd($header);
        // // validate
        foreach ($header as $key => $value) {
            $lheader = strtolower($value);
            $escapedItem = preg_replace('/[^_a-z]/', '', $lheader);
            // dd($escapedItem);
            array_push($escapedHeader, $escapedItem);
        }

        // $insert_sql  = "INSERT INTO `bookings` (`id`, `user_id`, `listing_id`, `folio_no`, `check_in`, `check_out`, `adult`, `infant`, `remark`, `nights`, `price_night`, `cleaning_fee`, `ota_fee`, `sst`, `price`, `source`, `category`, `status`, `created_at`, `updated_at`, `tourism_tax`, `discount_fee`) VALUES (NULL, '$user_id', '$listing_id', '$folio_no', '$check_in', '$check_out', '$adult', '$infant', '$remarks', '$nights', '$price_per_night', '$cleaning_fee', '$ota_fee', '$sst', '$price', '$reservation_source', '$category', '5',NULL, NULL, '0.00',
        // '$discount_fee')";

        //looping through othe columns
        while ($columns = fgetcsv($file)) {
            if ($columns[0] == "") {
                continue;
            }
            // dd($columns);
            $data = array_combine($escapedHeader, $columns);

            $already_exixts = EzeeBooking::where('SubBookingId', $data['res'])->first();
            if ($already_exixts) {
                continue;
            } else {
                $res1_listing_replace = str_replace(array('\'', '"', ',', ';', '<', '>'), '', $data[0]);
                $listing_txt_trim = trim($res1_listing_replace);

                $listing_exists = Listing::where('name', $listing_txt_trim)->first();
                // dd($listing_exists);
                if ($listing_exists) {
                    $ezeeBookingArray['listing_id'] = $listing_exists->id;
                } else {
                    $listingMissed['hotel_name'] = $listing_txt_trim;
                    continue;
                }
            }

            $user_exixts = User::where('name', $data['guestname'])->first();
            if ($user_exixts) {
                $ezeeBookingArray['user_id'] = $user_exixts->id;
            } else {
            }
            // $user_name = trim($colorsArray[0]);
            //         $res1 = str_replace( array( '\'', '"',
            //         ',' , ';', '<', '>' ), ' ', $user_name);
            //         echo $insert_user = "INSERT INTO `users` (`id`, `name`, `last_name`, `email`, `phone`, `country_code`, `address`, `password`, `provider`, `provider_token`, `status`, `ezee_tmp`, `remember_token`, `created_at`, `updated_at`) VALUES (NULL, '$res1', NULL, NULL, NULL, '60', NULL, '', NULL, NULL, '1', '0', NULL, '2020-05-24 13:51:40', '2022-08-01 00:29:26')";

        }
    }

    public function history(Request $request)
    {
        $logs = EzeeSyncLog::orderByDesc('created_at')->limit(50)->get();
        return view('admin.listing.history', compact('logs'));
    }

    public function history_api(Request $request)
    {
        set_time_limit(0);
        $startTime = microtime(true);
        $listings = EzeeGroup::all();
        if (empty($request->from_date) || empty($request->to_date)) {
            return back()->with('error', 'From date & To date fields are required, not be empty!');
        }

        if (!empty($request->from_date)) {
            $date_current = $request->from_date;
        }

        if (!empty($request->to_date)) {
            $newDate = $request->to_date;
            $first_date_of_month = date("Y-m-01");
            $new_date_folio = $request->to_date;
        }

        $newCount       = 0;
        $updatedCount   = 0;
        $unchangedCount = 0;
        $details        = [];
        // dd($newDate);
        // $newDate = $request->to_date;
        // // date("Y-m-d", strtotime("-3 days"));
        // $first_date_of_month = date("Y-m-01");

        // $new_date_folio = $request->to_date;
        // // date("Y-m-d", strtotime("-30 days"));
        $ezee_booking_folio = EzeeBooking::whereNotNull('book_id')->whereBetween('Start', [$date_current, $new_date_folio])->get();
        // dd($ezee_booking_folio);
        $postData_F['Request_Type'] = 'RetrieveListofBills';
        foreach ($ezee_booking_folio as $get_folio_no) {
            // echo '<pre>';
            // print_r($get_folio_no);
            $postData_F['Authentication'] = [
                'HotelCode' => 19676,
                'AuthCode' => '7181420090112972af-41e8-11ec-9',
                'BookingId' => $get_folio_no->SubBookingId,
            ];

            $pd_f['RES_Request'] = $postData_F;
            $payload_f = json_encode($pd_f);
            $notifyList = [];
            $ch_f = curl_init();
            curl_setopt(
                $ch_f,
                CURLOPT_URL,
                "https://live.ipms247.com/index.php/page/service.kioskconnectivity"
            );
            curl_setopt($ch_f, CURLOPT_POST, 1);
            curl_setopt($ch_f, CURLOPT_POSTFIELDS, $payload_f);
            curl_setopt(
                $ch_f,
                CURLOPT_HTTPHEADER,
                array('Content-Type:application/json')
            );
            curl_setopt($ch_f, CURLOPT_RETURNTRANSFER, true);
            $server_output_f = curl_exec($ch_f);
            curl_close($ch_f);
            $res_f = json_decode($server_output_f, true);
            $dataLog = DataLog::create([
                'title' => 'folio_no',
                'data' => $server_output_f,
                'related_id' => 19676,
                'status' => 'getFolio',
            ]);

            if (isset($res_f['Success']['FolioList'][0]['foliono'])) {
                Booking::where('id', $get_folio_no->book_id)
                    ->update(['server_folio_no' => $res_f['Success']['FolioList'][0]['foliono']]);
            }
        }
        foreach ($listings as $listing) {
            $ezeeGroupListing = \App\EzeeGroupListing::where('ezee_group_id', $listing->id)->first();
            $listingId = $ezeeGroupListing ? $ezeeGroupListing->listing_id : null;
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
            <FromDate>' . $date_current . '</FromDate>
            <ToDate>' . $newDate . '</ToDate>
        </RES_Request>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml',
                    'Cookie: AWSALB=7rZ/rDok4zKkcbOzsmCsjFd9Wd7WF91XvvLA9j91IpkFz8SDgQ02uIarkeLrm57vOkI+foqTGxVZH1hK+XeiFoPLHbZydZMrC0wC+ncgOxLCrwoDKWBVvdeET4tk; AWSALBCORS=7rZ/rDok4zKkcbOzsmCsjFd9Wd7WF91XvvLA9j91IpkFz8SDgQ02uIarkeLrm57vOkI+foqTGxVZH1hK+XeiFoPLHbZydZMrC0wC+ncgOxLCrwoDKWBVvdeET4tk; SSID=ri5l2lighfde46pq1du9ngvoen',
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            DataLog::create(['title' => 'ezee_raw_xml', 'data' => substr($response, 0, 8000), 'related_id' => $listing->id ?? 0, 'status' => 'debug']);

            $xml = simplexml_load_string(trim($response));
            $json = json_encode($xml);
            $res = json_decode($json, true);
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
                                            $sub_booking_id = null;
                                        } else {
                                            $sub_booking_id = $reserve1['BookingTran']['SubBookingId'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TransactionId'])) {
                                            $transaction_id = null;
                                        } else {
                                            $transaction_id = $reserve1['BookingTran']['TransactionId'];
                                        }

                                        if (is_array($reserve1['BookingTran']['IsConfirmed'])) {
                                            $is_confirmed = null;
                                        } else {
                                            $is_confirmed = $reserve1['BookingTran']['IsConfirmed'];
                                        }

                                        if (is_array($reserve1['BookingTran']['RateplanName'])) {
                                            $rateplanName = null;
                                        } else {
                                            $rateplanName = $reserve1['BookingTran']['RateplanName'];
                                        }

                                        if (is_array($reserve1['BookingTran']['RoomTypeName'])) {
                                            $roomTypeName = null;
                                        } else {
                                            $roomTypeName = $reserve1['BookingTran']['RoomTypeName'];
                                        }

                                        if (isset($reserve1['BookingTran']['RoomName']) && !is_array($reserve1['BookingTran']['RoomName'])) {
                                            $roomName = $reserve1['BookingTran']['RoomName'];
                                        } else {
                                            $roomName = null;
                                        }

                                        if (is_array($reserve1['BookingTran']['Createdatetime'])) {
                                            $created_at = null;
                                        } else {
                                            $created_at = $reserve1['BookingTran']['Createdatetime'];
                                        }

                                        if (is_array($reserve1['BookingTran']['Start'])) {
                                            $start = null;
                                        } else {
                                            $start = $reserve1['BookingTran']['Start'];
                                        }

                                        if (is_array($reserve1['BookingTran']['End'])) {
                                            $end = null;
                                        } else {
                                            $end = $reserve1['BookingTran']['End'];
                                        }

                                        if (is_array($reserve1['BookingTran']['CurrencyCode'])) {
                                            $currencyCode = null;
                                        } else {
                                            $currencyCode = $reserve1['BookingTran']['CurrencyCode'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalAmountAfterTax'])) {
                                            $totalAmountAfterTax = null;
                                        } else {
                                            $totalAmountAfterTax = $reserve1['BookingTran']['TotalAmountAfterTax'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalAmountBeforeTax'])) {
                                            $totalAmountBeforeTax = null;
                                        } else {
                                            $totalAmountBeforeTax = $reserve1['BookingTran']['TotalAmountBeforeTax'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalDiscount'])) {
                                            $totalDiscount = null;
                                        } else {
                                            $totalDiscount = $reserve1['BookingTran']['TotalDiscount'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalExtraCharge'])) {
                                            $totalExtraCharge = null;
                                        } else {
                                            $totalExtraCharge = $reserve1['BookingTran']['TotalExtraCharge'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TotalPayment'])) {
                                            $totalPayment = null;
                                        } else {
                                            $totalPayment = $reserve1['BookingTran']['TotalPayment'];
                                        }

                                        if (is_array($reserve1['BookingTran']['TACommision'])) {
                                            $tACommision = null;
                                        } else {
                                            $tACommision = $reserve1['BookingTran']['TACommision'];
                                        }

                                        if (is_array($reserve1['FirstName'])) {
                                            $first_name = null;
                                        } else {
                                            $first_name = $reserve1['FirstName'];
                                        }

                                        if (is_array($reserve1['LastName'])) {
                                            $last_name = null;
                                        } else {
                                            $last_name = $reserve1['LastName'];
                                        }

                                        if (is_array($reserve1['Mobile'])) {
                                            $mobile = null;
                                        } else {
                                            $mobile = $reserve1['Mobile'];
                                        }

                                        if (is_array($reserve1['Email'])) {
                                            $email = null;
                                        } else {
                                            $email = $reserve1['Email'];
                                        }

                                        if (is_array($reserve1['Country'])) {
                                            $country = null;
                                        } else {
                                            $country = $reserve1['Country'];
                                        }

                                        if (is_array($reserve1['BookingTran']['Source'])) {
                                            $source = null;
                                        } else {
                                            $source = $reserve1['BookedBy'];
                                        }

                                        $exist = EzeeBooking::where('SubBookingId', $sub_booking_id)->first();
                                        if (empty($exist)) {
                                            if ($sub_booking_id) {
                                                $exist = EzeeBooking::create([
                                                    'SubBookingId' => $sub_booking_id,
                                                    'TransactionId' => $transaction_id,
                                                    'IsConfirmed' => $is_confirmed,
                                                    'RateplanName' => $rateplanName,
                                                    'RoomTypeName' => $roomTypeName,
                                                    'RoomName' => $roomName,
                                                    'Start' => $start,
                                                    'End' => $end,
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
                                                    'Source' => preg_replace('/[^A-Za-z\. ]/', '', $source),
                                                    'created_at' => $created_at,
                                                ]);
                                                $newCount++;
                                                $details[] = ['action' => 'new', 'sub_booking_id' => $sub_booking_id, 'guest' => trim($first_name . ' ' . $last_name), 'room' => $roomName ?? $roomTypeName, 'check_in' => $start, 'check_out' => $end, 'amount' => $totalAmountAfterTax];
                                            }
                                        } else {
                                            // Detect changes
                                            $changes = [];
                                            if ($exist->RoomName !== $roomName) $changes['room_name'] = ['from' => $exist->RoomName, 'to' => $roomName];
                                            if ($exist->Start !== $start) $changes['check_in'] = ['from' => $exist->Start, 'to' => $start];
                                            if ($exist->End !== $end) $changes['check_out'] = ['from' => $exist->End, 'to' => $end];
                                            if ((float)$exist->TotalAmountAfterTax !== (float)$totalAmountAfterTax) $changes['amount'] = ['from' => $exist->TotalAmountAfterTax, 'to' => $totalAmountAfterTax];

                                            EzeeBooking::where("SubBookingId", $sub_booking_id)
                                                ->update([
                                                    'RoomTypeName' => $roomTypeName,
                                                    'RoomName' => $roomName,
                                                    'Start' => $start,
                                                    'End' => $end,
                                                    "TotalExtraCharge" => $totalExtraCharge,
                                                    "TotalAmountAfterTax" => $totalAmountAfterTax,
                                                    "TotalAmountBeforeTax" => $totalAmountBeforeTax,
                                                ]);
                                            if (count($changes)) {
                                                $updatedCount++;
                                                $details[] = ['action' => 'updated', 'sub_booking_id' => $sub_booking_id, 'guest' => trim($first_name . ' ' . $last_name), 'room' => $roomName ?? $roomTypeName, 'check_in' => $start, 'check_out' => $end, 'amount' => $totalAmountAfterTax, 'changes' => $changes];
                                            } else {
                                                $unchangedCount++;
                                            }
                                        }
                                    } else {
                                        foreach ($reserve1['BookingTran'] as $reserve_array_value) {

                                            if (is_array($reserve_array_value['SubBookingId'])) {
                                                $sub_booking_id = null;
                                            } else {
                                                $sub_booking_id = $reserve_array_value['SubBookingId'];
                                            }

                                            if (is_array($reserve_array_value['TransactionId'])) {
                                                $transaction_id = null;
                                            } else {
                                                $transaction_id = $reserve_array_value['TransactionId'];
                                            }

                                            if (is_array($reserve_array_value['IsConfirmed'])) {
                                                $is_confirmed = null;
                                            } else {
                                                $is_confirmed = $reserve_array_value['IsConfirmed'];
                                            }

                                            if (is_array($reserve_array_value['RateplanName'])) {
                                                $rateplanName = null;
                                            } else {
                                                $rateplanName = $reserve_array_value['RateplanName'];
                                            }

                                            if (is_array($reserve_array_value['RoomTypeName'])) {
                                                $roomTypeName = null;
                                            } else {
                                                $roomTypeName = $reserve_array_value['RoomTypeName'];
                                            }

                                            if (isset($reserve_array_value['RoomName']) && !is_array($reserve_array_value['RoomName'])) {
                                                $roomName = $reserve_array_value['RoomName'];
                                            } else {
                                                $roomName = null;
                                            }

                                            if (is_array($reserve_array_value['Start'])) {
                                                $start = null;
                                            } else {
                                                $start = $reserve_array_value['Start'];
                                            }

                                            if (is_array($reserve_array_value['End'])) {
                                                $end = null;
                                            } else {
                                                $end = $reserve_array_value['End'];
                                            }

                                            if (is_array($reserve_array_value['CurrencyCode'])) {
                                                $currencyCode = null;
                                            } else {
                                                $currencyCode = $reserve_array_value['CurrencyCode'];
                                            }

                                            if (is_array($reserve_array_value['TotalAmountAfterTax'])) {
                                                $totalAmountAfterTax = null;
                                            } else {
                                                $totalAmountAfterTax = $reserve_array_value['TotalAmountAfterTax'];
                                            }

                                            if (is_array($reserve_array_value['TotalAmountBeforeTax'])) {
                                                $totalAmountBeforeTax = null;
                                            } else {
                                                $totalAmountBeforeTax = $reserve_array_value['TotalAmountBeforeTax'];
                                            }

                                            if (is_array($reserve_array_value['TotalDiscount'])) {
                                                $totalDiscount = null;
                                            } else {
                                                $totalDiscount = $reserve_array_value['TotalDiscount'];
                                            }

                                            if (is_array($reserve_array_value['TotalExtraCharge'])) {
                                                $totalExtraCharge = null;
                                            } else {
                                                $totalExtraCharge = $reserve_array_value['TotalExtraCharge'];
                                            }

                                            if (is_array($reserve_array_value['TotalPayment'])) {
                                                $totalPayment = null;
                                            } else {
                                                $totalPayment = $reserve_array_value['TotalPayment'];
                                            }

                                            if (is_array($reserve_array_value['TACommision'])) {
                                                $tACommision = null;
                                            } else {
                                                $tACommision = $reserve_array_value['TACommision'];
                                            }

                                            if (is_array($reserve1['FirstName'])) {
                                                $first_name = null;
                                            } else {
                                                $first_name = $reserve1['FirstName'];
                                            }

                                            if (is_array($reserve1['LastName'])) {
                                                $last_name = null;
                                            } else {
                                                $last_name = $reserve1['LastName'];
                                            }

                                            if (is_array($reserve1['Mobile'])) {
                                                $mobile = null;
                                            } else {
                                                $mobile = $reserve1['Mobile'];
                                            }

                                            if (is_array($reserve1['Email'])) {
                                                $email = null;
                                            } else {
                                                $email = $reserve1['Email'];
                                            }

                                            if (is_array($reserve1['Country'])) {
                                                $country = null;
                                            } else {
                                                $country = $reserve1['Country'];
                                            }

                                            if (is_array($reserve_array_value['Source'])) {
                                                $source = null;
                                            } else {
                                                $source = $reserve_array_value['Source'];
                                            }

                                            if (is_array($reserve_array_value['Createdatetime'])) {
                                                $created_at = null;
                                            } else {
                                                $created_at = $reserve_array_value['Createdatetime'];
                                            }

                                            $exist = EzeeBooking::where('SubBookingId', $sub_booking_id)->first();

                                            if (empty($exist)) {
                                                if ($sub_booking_id) {
                                                    $exist = EzeeBooking::create([
                                                        'SubBookingId' => $sub_booking_id,
                                                        'TransactionId' => $transaction_id,
                                                        'IsConfirmed' => $is_confirmed,
                                                        'RateplanName' => $rateplanName,
                                                        'RoomTypeName' => $roomTypeName,
                                                        'RoomName' => $roomName,
                                                        'Start' => $start,
                                                        'End' => $end,
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
                                                        'Source' => preg_replace('/[^A-Za-z\. ]/', '', $source),
                                                        'created_at' => $created_at,
                                                    ]);
                                                    $newCount++;
                                                    $details[] = ['action' => 'new', 'sub_booking_id' => $sub_booking_id, 'guest' => trim($first_name . ' ' . $last_name), 'room' => $roomName ?? $roomTypeName, 'check_in' => $start, 'check_out' => $end, 'amount' => $totalAmountAfterTax];
                                                }
                                            } else {
                                                $changes = [];
                                                if ($exist->RoomName !== $roomName) $changes['room_name'] = ['from' => $exist->RoomName, 'to' => $roomName];
                                                if ($exist->Start !== $start) $changes['check_in'] = ['from' => $exist->Start, 'to' => $start];
                                                if ($exist->End !== $end) $changes['check_out'] = ['from' => $exist->End, 'to' => $end];
                                                if ((float)$exist->TotalAmountAfterTax !== (float)$totalAmountAfterTax) $changes['amount'] = ['from' => $exist->TotalAmountAfterTax, 'to' => $totalAmountAfterTax];

                                                EzeeBooking::where("SubBookingId", $sub_booking_id)
                                                    ->update([
                                                        'RoomTypeName' => $roomTypeName,
                                                        'RoomName' => $roomName,
                                                        'Start' => $start,
                                                        'End' => $end,
                                                        "TotalExtraCharge" => $totalExtraCharge,
                                                        "TotalAmountAfterTax" => $totalAmountAfterTax,
                                                        "TotalAmountBeforeTax" => $totalAmountBeforeTax,
                                                    ]);
                                                if (count($changes)) {
                                                    $updatedCount++;
                                                    $details[] = ['action' => 'updated', 'sub_booking_id' => $sub_booking_id, 'guest' => trim($first_name . ' ' . $last_name), 'room' => $roomName ?? $roomTypeName, 'check_in' => $start, 'check_out' => $end, 'amount' => $totalAmountAfterTax, 'changes' => $changes];
                                                } else {
                                                    $unchangedCount++;
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

        $duration = round(microtime(true) - $startTime, 2);
        EzeeSyncLog::create([
            'from_date'       => $date_current,
            'to_date'         => $newDate,
            'new_count'       => $newCount,
            'updated_count'   => $updatedCount,
            'unchanged_count' => $unchangedCount,
            'total_count'     => $newCount + $updatedCount + $unchangedCount,
            'duration_seconds'=> $duration,
            'details'         => array_filter($details, fn($d) => $d['action'] !== 'unchanged'),
            'ran_by'          => Auth::id(),
        ]);

        return back()->with('success', "Sync complete in {$duration}s — {$newCount} new, {$updatedCount} updated, {$unchangedCount} unchanged.");
    }
}
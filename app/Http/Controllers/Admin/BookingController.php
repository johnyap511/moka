<?php

namespace App\Http\Controllers\Admin;

use App\Booking;
use App\DataLog;
use App\EzeeGroup;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Import\BookingImport;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        // $this->historicalAPI();
    }

    public static function historicalAPI()
    {
        ini_set('max_execution_time', 300);
        $listings = EzeeGroup::all();
        $date_current = date("Y-m-d");

        $newDate = date("Y-m-d", strtotime("-3 days"));
        $first_date_of_month = date("Y-m-01");

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

            $xml = simplexml_load_string(trim($response));
            $json = json_encode($xml);
            $res = json_decode($json, true);
            $reservation_data_ezee = array();
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
                                foreach ($reserve as $reserve1) {
                                    //getting key if exist to find array inside array or elements
                                    if (array_key_exists('IsConfirmed', $reserve1['BookingTran'])) {
                                        if (is_array($reserve1['BookingTran']['SubBookingId'])) {
                                            $sub_booking_id = null;
                                        } else {
                                            $sub_booking_id = $reserve1['BookingTran']['SubBookingId'];
                                        }

                                        // The channel's own reference — the number on an OTA statement.
                                        $voucher_no = is_array($reserve1['BookingTran']['VoucherNo'] ?? null) ? null : ($reserve1['BookingTran']['VoucherNo'] ?? null);
                                        // EZEE flags an amended reservation and when it changed. The booking API
                                        // reports only the final room, so a mid-stay move is otherwise invisible.
                                        $ezee_status = is_array($reserve1['BookingTran']['Status'] ?? null) ? null : ($reserve1['BookingTran']['Status'] ?? null);
                                        $ezee_current_status = is_array($reserve1['BookingTran']['CurrentStatus'] ?? null) ? null : ($reserve1['BookingTran']['CurrentStatus'] ?? null);
                                        $ezee_modified_at = is_array($reserve1['BookingTran']['Modifydatetime'] ?? null) ? null : ($reserve1['BookingTran']['Modifydatetime'] ?? null);

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
                                            $source = $reserve1['BookingTran']['Source'];
                                        }

                                        $exist = EzeeBooking::where(
                                            [
                                                ['SubBookingId', $sub_booking_id],
                                                ['TransactionId', $transaction_id],
                                            ]
                                        )->first();

                                        if (empty($exist)) {
                                            $dataLog = DataLog::create([
                                                'title' => 'sendnotify',
                                                'data' => '',
                                                'related_id' => 20317,
                                                'status' => 'started',
                                            ]);
                                            if ($sub_booking_id) {
                                                $exist = EzeeBooking::create([
                                                    'SubBookingId' => $sub_booking_id,
                                                    'ezee_status' => $ezee_status,
                                                    'ezee_current_status' => $ezee_current_status,
                                                    'ezee_modified_at' => $ezee_modified_at,
                                                    'VoucherNo' => $voucher_no,
                                                    'TransactionId' => $transaction_id,
                                                    'IsConfirmed' => $is_confirmed,
                                                    'RateplanName' => $rateplanName,
                                                    'VoucherNo' => $voucher_no,
                                                    'RoomTypeName' => $roomTypeName,
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
                                                    'created_at' => $created_at,
                                                ]);
                                            }
                                        } else {
                                            // EZEE omits the unit on some reservations. Writing that blank back
                                            // would wipe a unit we already hold, so only refresh what actually
                                            // arrived.
                                            $refresh = array_filter([
                                                'ezee_status' => $ezee_status,
                                                'ezee_current_status' => $ezee_current_status,
                                                'ezee_modified_at' => $ezee_modified_at,
                                                        'VoucherNo' => $voucher_no,
                                                        'RoomTypeName' => $roomTypeName,
                                                        'RoomName' => $roomName,
                                                        'Start' => $start,
                                                        'End' => $end,
                                                        'TotalAmountBeforeTax' => $totalAmountBeforeTax,
                                                        'TotalAmountAfterTax' => $totalAmountAfterTax,
                                                        'TotalDiscount' => $totalDiscount,
                                                        'TotalExtraCharge' => $totalExtraCharge,
                                                        'TotalPayment' => $totalPayment,
                                                        'TACommision' => $tACommision,
                                            ], fn ($v) => $v !== null && $v !== '');

                                            if ($refresh) {
                                                // save() rather than a query-builder update: a builder update skips
                                                // model events, and the amendment hook has to see the change.
                                                $exist->fill($refresh)->save();
                                            }
                                        }
                                    } else {
                                        foreach ($reserve1['BookingTran'] as $reserve_array_value) {

                                            if (is_array($reserve_array_value['SubBookingId'])) {
                                                $sub_booking_id = null;
                                            } else {
                                                $sub_booking_id = $reserve_array_value['SubBookingId'];
                                            }

                                            // The channel's own reference — the number on an OTA statement.
                                            $voucher_no = is_array($reserve_array_value['VoucherNo'] ?? null) ? null : ($reserve_array_value['VoucherNo'] ?? null);
                                            // EZEE flags an amended reservation and when it changed. The booking API
                                            // reports only the final room, so a mid-stay move is otherwise invisible.
                                            $ezee_status = is_array($reserve_array_value['Status'] ?? null) ? null : ($reserve_array_value['Status'] ?? null);
                                            $ezee_current_status = is_array($reserve_array_value['CurrentStatus'] ?? null) ? null : ($reserve_array_value['CurrentStatus'] ?? null);
                                            $ezee_modified_at = is_array($reserve_array_value['Modifydatetime'] ?? null) ? null : ($reserve_array_value['Modifydatetime'] ?? null);

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

                                            $exist = EzeeBooking::where(
                                                [
                                                    ['SubBookingId', $sub_booking_id],
                                                    ['TransactionId', $transaction_id],
                                                ]
                                            )->first();

                                            if (empty($exist)) {
                                                $dataLog = DataLog::create([
                                                    'title' => 'sendnotify',
                                                    'data' => '',
                                                    'related_id' => 20317,
                                                    'status' => 'started',
                                                ]);
                                                if ($sub_booking_id) {

                                                    $exist = EzeeBooking::create([
                                                        'SubBookingId' => $sub_booking_id,
                                                        'ezee_status' => $ezee_status,
                                                        'ezee_current_status' => $ezee_current_status,
                                                        'ezee_modified_at' => $ezee_modified_at,
                                                        'VoucherNo' => $voucher_no,
                                                        'TransactionId' => $transaction_id,
                                                        'IsConfirmed' => $is_confirmed,
                                                        'RateplanName' => $rateplanName,
                                                        'VoucherNo' => $voucher_no,
                                                        'RoomTypeName' => $roomTypeName,
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
                                                        'created_at' => $created_at,
                                                    ]);
                                                }
                                            } else {
                                                // EZEE omits the unit on some reservations. Writing that blank back
                                                // would wipe a unit we already hold, so only refresh what actually
                                                // arrived.
                                                $refresh = array_filter([
                                                    'ezee_status' => $ezee_status,
                                                    'ezee_current_status' => $ezee_current_status,
                                                    'ezee_modified_at' => $ezee_modified_at,
                                                            'VoucherNo' => $voucher_no,
                                                            'RoomTypeName' => $roomTypeName,
                                                            'RoomName' => $roomName,
                                                            'Start' => $start,
                                                            'End' => $end,
                                                            'TotalAmountBeforeTax' => $totalAmountBeforeTax,
                                                            'TotalAmountAfterTax' => $totalAmountAfterTax,
                                                            'TotalDiscount' => $totalDiscount,
                                                            'TotalExtraCharge' => $totalExtraCharge,
                                                            'TotalPayment' => $totalPayment,
                                                            'TACommision' => $tACommision,
                                                ], fn ($v) => $v !== null && $v !== '');

                                                if ($refresh) {
                                                    // save() rather than a query-builder update: a builder update skips
                                                    // model events, and the amendment hook has to see the change.
                                                    $exist->fill($refresh)->save();
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

public function index(Request $request)
{
    Log::info('Booking index called', [
        'ajax' => $request->ajax(),
        'all_params' => $request->all(),
        'url' => $request->fullUrl()
    ]);

    // For DataTables server-side processing (AJAX requests)
    if ($request->ajax()) {
        Log::info('Processing DataTables AJAX request');
        return $this->getDataTablesData($request);
    }

    Log::info('Processing regular page request');
    
    // For regular page load (non-AJAX requests)
    $id = $request->id;
    $userName = $request->user;
    $listingName = $request->listing;
    $from_date = $request->from_date;
    $to_date = $request->to_date;
    $checkin_date = $request->checkin_date;
    $checkinto_date = $request->checkinto_date;

    $query = Booking::with(['user:id,name,last_name,email', 'listing:id,name,title'])
        ->where('status', '>=', 3)
        ->orderBy('check_in', 'desc');

    // One box covering what production split across three: booking id, guest
    // name and listing name, plus folio numbers since those get quoted to
    // guests and are what people paste in.
    if ($request->filled('q')) {
        $q = trim($request->q);
        $query->where(function ($sq) use ($q) {
            // bookings has no name column; the guest is on the user, the RES
            // number on the linked EZEE reservation.
            $sq->where('folio_no', 'LIKE', "%{$q}%")
               ->orWhere('server_folio_no', 'LIKE', "%{$q}%")
               ->orWhereHas('ezeeBooking', fn($e) => $e->where('SubBookingId', 'LIKE', "%{$q}%"))
               ->orWhereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$q}%")
                   ->orWhere('last_name', 'LIKE', "%{$q}%")
                   ->orWhere('email', 'LIKE', "%{$q}%"))
               ->orWhereHas('listing', fn($l) => $l->where('name', 'LIKE', "%{$q}%")
                   ->orWhere('title', 'LIKE', "%{$q}%"));

            if (ctype_digit($q)) {
                $sq->orWhere('id', (int) $q);
            }
        });
    }

    // Status filter
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Check-in date range filter
    if ($request->filled('from_date')) {
        $query->where('check_in', '>=', $request->from_date);
    }
    if ($request->filled('to_date')) {
        $query->where('check_in', '<=', $request->to_date);
    }

    $books = $query->paginate(50);

    $totalBookings  = Booking::where('status', '>=', 3)->count();
    $confirmedCount = Booking::where('status', 5)->count();
    $pendingCount   = Booking::whereIn('status', [1, 2, 3])->count();
    $cancelledCount = Booking::where('status', 0)->count();

    $action = $request->action;
    return view('admin.listing.book.index', compact(
        'books',
        'action',
        'id',
        'userName',
        'listingName',
        'from_date',
        'to_date',
        'checkin_date',
        'checkinto_date',
        'totalBookings',
        'confirmedCount',
        'pendingCount',
        'cancelledCount'
    ));
}

protected function getDataTablesData(Request $request)
{
    $startTime = microtime(true); // Initialize at the start
    $timeoutSeconds = 55; // Leave buffer before server timeout

    try {
        Log::info('DataTables method called', $request->all());

        // Load relationships with query optimization
        $query = Booking::query()
            ->select([
                'bookings.id',
                'bookings.user_id',
                'bookings.listing_id',
                'bookings.folio_no',
                'bookings.server_folio_no',
                'bookings.check_in',
                'bookings.check_out',
                'bookings.created_at'
            ])
            ->with([
                'user:id,name,last_name,email',
                'listing:id,name,title',
                'ezeeBooking:id,book_id,SubBookingId,FirstName,LastName'
            ])
            ->where('bookings.status', '>=', 3);

        // Apply individual search filters
        if ($request->has('id') && !empty($request->id)) {
            $query->where('id', $request->id);
        }
        
        if ($request->has('user') && !empty($request->user)) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->user}%")
                  ->orWhere('last_name', 'LIKE', "%{$request->user}%")
                  ->orWhere('email', 'LIKE', "%{$request->user}%");
            });
        }
        
        if ($request->has('listing') && !empty($request->listing)) {
            $query->whereHas('listing', function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->listing}%")
                  ->orWhere('title', 'LIKE', "%{$request->listing}%");
            });
        }

        // Apply date range filters
        if ($request->has('from_date') && !empty($request->from_date) && 
            $request->has('to_date') && !empty($request->to_date)) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->has('checkin_date') && !empty($request->checkin_date) && 
            $request->has('checkinto_date') && !empty($request->checkinto_date)) {
            $query->whereBetween('check_in', [$request->checkin_date, $request->checkinto_date]);
        }

        // Get total records count
        $totalRecords = $query->count();
        Log::info("Total records found: {$totalRecords}");

        // Global search from DataTables
        $searchValue = $request->search['value'] ?? '';
        if (!empty($searchValue)) {
            $searchTerm = trim($searchValue);
            
            // Only process search if term is at least 3 characters
            if (strlen($searchTerm) >= 3) {
                // Set chunk size based on data volume and performance
                $chunkSize = min(300, (int)($request->length * 1.5)); // Adaptive chunk size
                ini_set('max_execution_time', 125); // Extend timeout for larger datasets
                
                $query->where(function($q) use ($searchTerm, $chunkSize, $startTime, $timeoutSeconds) {
                    // Check for timeout periodically
                    if ((microtime(true) - $startTime) > $timeoutSeconds) {
                        throw new \RuntimeException('Query timeout - Please refine your search criteria or reduce the date range');
                    }
                    // First prioritize exact matches
                    $q->where('bookings.id', 'LIKE', "{$searchTerm}")
                      ->orWhere('bookings.folio_no', 'LIKE', "{$searchTerm}")
                      ->orWhere('bookings.server_folio_no', 'LIKE', "{$searchTerm}");

                    // Then add prefix matches for high-priority fields
                    $q->orWhere('bookings.id', 'LIKE', "{$searchTerm}%")
                      ->orWhere('bookings.folio_no', 'LIKE', "{$searchTerm}%")
                      ->orWhere('bookings.server_folio_no', 'LIKE', "{$searchTerm}%");

                    // Date searches should be exact format matches only
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
                        $q->orWhere('bookings.check_in', 'LIKE', "{$searchTerm}%")
                          ->orWhere('bookings.check_out', 'LIKE', "{$searchTerm}%");
                    }
                    
                    // User relationship search with chunking
                    $q->orWhereHas('user', function($userQuery) use ($searchTerm, $chunkSize) {
                        $userQuery->where(function($uq) use ($searchTerm) {
                            $uq->where('name', 'LIKE', "{$searchTerm}%")
                              ->orWhere('last_name', 'LIKE', "{$searchTerm}%");
                        })
                        ->select('id', 'name', 'last_name')
                        ->limit($chunkSize);
                    });
                    
                    // Listing relationship search with chunking
                    $q->orWhereHas('listing', function($listingQuery) use ($searchTerm, $chunkSize) {
                        $listingQuery->where(function($lq) use ($searchTerm) {
                            $lq->where('name', 'LIKE', "{$searchTerm}%")
                              ->orWhere('title', 'LIKE', "{$searchTerm}%");
                        })
                        ->select('id', 'name', 'title')
                        ->limit($chunkSize);
                    });
                    
                   // Instead of orWhereHas, use a subquery
                    $ezeeSubquery = EzeeBooking::where('SubBookingId', 'LIKE', "{$searchTerm}%")
                         ->select('book_id')
                         ->limit(100)
                         ->get()
                         ->pluck('book_id')
                         ->toArray();

                    if (!empty($ezeeSubquery)) {
                    $q->orWhereIn('bookings.id', $ezeeSubquery);
                    }


                });
            } else {
                // For very short search terms, only search main booking fields
                $query->where(function($q) use ($searchTerm) {
                    $q->where('bookings.id', 'LIKE', "{$searchTerm}%")
                      ->orWhere('bookings.folio_no', 'LIKE', "{$searchTerm}%")
                      ->orWhere('bookings.server_folio_no', 'LIKE', "{$searchTerm}%");
                });
            }
        }

        // Get filtered count with timeout check
        try {
            // Use faster counting for large datasets
            $filteredRecords = $query->take(10000)->count();
            
            // Check if taking too long
            if ((microtime(true) - $startTime) > $timeoutSeconds) {
                throw new \RuntimeException('Response timeout - Try reducing the date range or adding more specific filters');
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'timeout') !== false) {
                return response()->json([
                    'draw' => intval($request->draw ?? 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => $e->getMessage(),
                    'suggestion' => 'Try reducing the date range or adding more specific search terms'
                ]);
            }
            throw $e;
        }

        // Apply ordering with timeout handling
        if (!empty($request->order)) {
            $orderColumnIndex = $request->order[0]['column'];
            $orderColumnName = $request->columns[$orderColumnIndex]['data'] ?? 'id';
            $orderDirection = $request->order[0]['dir'] ?? 'desc';
            
            $orderColumn = $this->getSafeOrderColumn($orderColumnName);
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Apply pagination with timeout handling
        $start = $request->start ?? 0;
        $length = min($request->length ?? 25, 100); // Limit maximum rows per request
        
        // Use efficient pagination for large datasets
        $bookings = $query->skip($start)->take($length)->get();
        
        // Final timeout check
        if ((microtime(true) - $startTime) > $timeoutSeconds) {
            throw new \RuntimeException('Response timeout - Try reducing the date range or adding more specific filters');
        }
        Log::info("Retrieved {$bookings->count()} bookings with relationships");

       // Format data with proper names
$data = [];

foreach ($bookings as $book) {
    // Get reservation number and server folio
    $reservationNo = $this->getReservationNo($book);
    $serverFolioNo = $book->server_folio_no ?? '';

    // Initialize final variables
    $finalReservationNo = '';
    $finalServerFolioNo = '';

    // Determine final reservation number (starts with R)
    if (!empty($reservationNo) && strtoupper(substr($reservationNo, 0, 1)) === 'R') {
        $finalReservationNo = $reservationNo; // Reservation number
    }

    // Determine final server folio number
    if (!empty($serverFolioNo) && strtoupper(substr($serverFolioNo, 0, 1)) === 'F') {
        // Server folio already starts with F
        $finalServerFolioNo = $serverFolioNo;
    } elseif (empty($serverFolioNo) && !empty($reservationNo) && strtoupper(substr($reservationNo, 0, 1)) === 'F') {
        // If server folio is empty but reservation starts with F, use that
        $finalServerFolioNo = $reservationNo;
    } else {
        // Otherwise leave it empty
        $finalServerFolioNo = $serverFolioNo;
    }

    // Build the final array
    $data[] = [
        'id' => $book->id,
        'reservation_no' => $finalReservationNo,
        'created_at' => $book->created_at ? $book->created_at->format('Y-m-d H:i:s') : '',
        'user' => $this->getUserName($book),
        'user_id' => $book->user_id,
        'listing' => $this->getListingName($book),
        'listing_id' => $book->listing_id,
        'check_in' => $book->check_in,
        'check_out' => $book->check_out,
        'server_folio_no' => $finalServerFolioNo,
        'action' => $this->getActionButtons($book)
    ];
}


        Log::info('Sending DataTables response with formatted data');

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        Log::error('DataTables ERROR: ' . $errorMessage);
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        // Get the draw parameter safely
        $draw = 1;
        if (isset($request) && isset($request->draw)) {
            $draw = intval($request->draw);
        }
        
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Server error: ' . $errorMessage
        ], 500);
    }
}

private function getSafeOrderColumn($columnName)
{
    $safeColumns = [
        'id' => 'bookings.id',
        'created_at' => 'bookings.created_at',
        'check_in' => 'bookings.check_in',
        'check_out' => 'bookings.check_out',
        'server_folio_no' => 'bookings.server_folio_no',
        'folio_no' => 'bookings.folio_no'
    ];
    
    return $safeColumns[$columnName] ?? 'created_at';
}

private function getReservationNo($book)
{
    // Priority: EzeeBooking SubBookingId, then folio_no, then server_folio_no
    if ($book->ezeeBooking && $book->ezeeBooking->SubBookingId) {
        return $book->ezeeBooking->SubBookingId;
    }
    
    if (!empty($book->folio_no)) {
        return $book->folio_no;
    }
    
    if (!empty($book->server_folio_no)) {
        return $book->server_folio_no;
    }
    
    return 'N/A';
}

private function getUserName($book)
{
    // If user relationship is loaded and exists
    if ($book->user) {
        return $book->user->name . ' ' . $book->user->last_name;
    }
    
    // If ezeeBooking has user info
    if ($book->ezeeBooking) {
        return $book->ezeeBooking->FirstName . ' ' . $book->ezeeBooking->LastName;
    }
    
    return 'User ID: ' . $book->user_id ?? '';
}

private function getListingName($book)
{
    // If listing relationship is loaded and exists
    if ($book->listing) {
        return $book->listing->name;
    }
    
    return 'Listing ID: ' . $book->listing_id;
}

private function getActionButtons($book)
{
    return '<div class="action-buttons">' .
           '<a href="' . url('/admin/book/' . $book->id) . '" class="btn-action btn-detail" title="View Details">Detail</a>' .
           '<a href="' . url('/admin/book/' . $book->id . '/edit') . '" class="btn-action btn-edit" title="Edit Booking">Edit</a>' .
           '<form method="post" action="' . url('/admin/book/' . $book->id) . '" style="display: inline;" class="delete-form">' .
           csrf_field() .
           '<input type="hidden" name="_method" value="DELETE">' .
           '<button type="submit" class="btn-action btn-delete" title="Delete Booking" onclick="return confirmDelete(' . $book->id . ')">Delete</button>' .
           '</form>' .
           '</div>';
}



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $listings = Listing::where('status', 1)->orderBy('name')->get();

        return view('admin.listing.book.create', compact('listings'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $book = Booking::find($id);
        if (!$book) {
            return redirect('/admin/book')->with('error', 'Booking not found.');
        }
        $user = User::find($book->user_id);
        $listing = Listing::find($book->listing_id);
        $owner = $listing ? User::find($listing->user_id) : null;
        return view('admin.listing.book.detail', compact('book', 'user', 'listing', 'owner'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $book = Booking::find($id);
        if (empty($book)) {
            return back()->with('error', 'Something Went wrong!');
        }
        $user = User::find($book->user_id);
        return view('admin.listing.book.edit', compact('book', 'user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|max:120',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adult' => 'required|numeric',
            'infant' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only("name", "last_name", "email", "phone");
        $bookData = $request->only("folio_no", "check_in", "check_out", "adult", "infant", 'price_night', 'cleaning_fee', 'ota_fee', 'sst', 'sst_cf', 'discount_fee', 'price', "remark", "source", "category");
        if ($bookData['check_out'] <= $bookData['check_in']) {
            return back()->with('error', 'The check out should be bigger than check in!')->withInput();
        }
        $book = Booking::find($id);

        $today = date("Y-m-d");
        $books = Booking::where([['listing_id', $book->listing_id], ['status', 5], ['check_out', '>=', $today]])->get();
        foreach ($books as $book2) {
            if ($book2->id != $id && $book2->check_in < $bookData['check_out'] && $book2->check_out > $bookData['check_in']) {
                return back()->with('error', 'These dates are not available!')->withInput();
            }
        }

        if (!empty($request->phone)) {
            $user = User::where('phone', $request->phone)->first();
        } else {
            $user = User::where('email', $request->email)->first();
        }

        $ezeeBooking = EzeeBooking::where('book_id', $id)->first();
        if (empty($user) && empty($ezeeBooking)) {
            if (empty($request->name)) {
                $data['name'] = '';
            }
            $user = User::create($data);
            $role = Role::find(2);
            $user->attachRole($role);
        } elseif (!empty($user)) {
            $user->update($data);
        }

        $bookData['user_id'] = $user->id ?? null;
        //        $bookData['listing_id'] = $id;
        if (!isset($bookData['infant'])) {
            $bookData['infant'] = 0;
        }

        $listing = Listing::find($book->listing_id);
        $bookingStartDate = date_create($bookData['check_in']);
        $bookingEndDate = date_create($bookData['check_out']);
        $interval = date_diff($bookingStartDate, $bookingEndDate);
        $bookData['nights'] = $interval->format('%a');

        //        $bookData['price'] = 0;
        //        if(empty($request->price)){
        //            while($bookingStartDate <= $bookingEndDate){
        //                $price = ListingPrice::where([['listing_id', $listing->id], ['date', date_format($bookingStartDate, "Y-m-d")]])->first();
        //                if(empty($price)){
        //                    $price = $listing->default_price;
        //                }else{
        //                    $price = $price->price;
        //                }
        //                $bookData['price'] = $bookData['price']+ $price;
        //                $bookingStartDate->modify('+1 day');
        //            }
        //        }else{
        //            $bookData['price'] = $request->price*$bookData['nights'];
        //        }

        $bookData['status'] = 5;
        $book->update($bookData);

        $email = $user->email ?? '';
        if (!empty($email)) {
            $data22['status'] = 5;
            $data22['name'] = $user->name;
            $data22['check_in'] = $book->check_in;
            $data22['check_out'] = $book->check_out;
            $data22['listing_name'] = $listing->name;
            //            Mail::to($email)->queue(new BookingStatusMail($data22));
        }

        return redirect('/admin/book')->with('success', 'Booking is updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $book = Booking::find($id);
        if (!empty($book)) {
            $ezee = EzeeBooking::where('book_id', $id)->first();
            if (!blank($ezee)) {
                $ezee->update(['status' => 5, 'book_id' => null]);
            }
            $book->delete();
        }
        return redirect('/admin/book')->with('success', 'Booking is deleted successfully!');
    }

    /**
     * Import Excel
     * @return \Illuminate\Http\Response
     */
    public function importExcel(Request $request)
    {
        $validator = Validator::make(
            [
                'file' => $request->file,
                'extension' => strtolower($request->file->getClientOriginalExtension()),
            ],
            [
                'file' => 'required',
                'extension' => 'required|in:csv,xlsx,xls',
            ]
        );
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $import = new BookingImport();
        Excel::import($import, $request->file);
        $excelResult = $import->getResData();
        $books = Booking::where('status', '>=', 3)->get();
        return view('admin.listing.book.index', compact('books', 'excelResult'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        // listing() drops the archived scope: a booking on a property we have
        // handed back still belongs in the export, and Listing::find() resolved
        // it to null, so 10,498 rows exported with a blank listing name.
        // lazyById keeps the query chunked — this runs over 65,000 bookings.
        $bookings = Booking::where('status', '>=', 3)
            ->with(['user', 'listing', 'ezeeBooking'])
            ->lazyById(500);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells('A1:N1');
        $exportData[0] = ['MOKA'];
        $exportData[1] = [
            'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights', 'Reservation Source', 'Price per Night', 'Cleaning Fee', 'SST(CF)',
            'OTA', 'SST', 'Total', 'Remarks',
        ];
        $x = 2;
        $sst = 0;
        foreach ($bookings as $booking) {
            $todays = $booking->check_in;
            $ota_cal = (($booking->price_night * $booking->nights));
            $new_ota = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee;
            $new_ota1 = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee + $booking->sst + $booking->sst_cf;
            $ota = '';
            // Stored, not recalculated: the fee stamped when the booking was
            // assigned. Recomputing here applied today's rates to historical
            // bookings, so the same export run twice either side of a rate
            // change produced two different documents.
            $ota = $booking->ota_fee;
            $sst = $booking->sst; // stored figure: a tenancy carries its SST too

            $otaText = '';
            if ($booking->source == 'Booking') {
                    $otaText = 'Booking.com';
                } elseif (preg_match('/^(pms|walk|google|book on google|internet|booking engine|website)/i', trim((string) $booking->source))) {
                    // Direct channels: PMS, walk-in, Google, Internet Booking Engine.
                    // Finance treats them as one channel, "Website", at the 8% fee.
                    $otaText = 'Website';
                } else {
                    $otaText = $booking->source;
                }

                // eZee's own folio number first (the one staff see in eZee), then the
            // folio typed at booking time, then MOKA's internal number.
            $ezee      = $booking->ezeeBooking;
            $ezeeFolio = $ezee->folio_no ?? '';
            if ($ezeeFolio === '' && !empty($booking->server_folio_no)) {
                $ezeeFolio = $booking->server_folio_no;
            }
            $folio_no  = $ezeeFolio !== '' ? $ezeeFolio : $booking->folio_no;
            // The guest as eZee names them; hand-keyed rows sit under a staff account.
            $firstName = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) $ezee->FirstName) : ($booking->user->name ?? '');
            $lastName  = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) ($ezee->LastName ?? '')) : ($booking->user->last_name ?? '');

            $total_charges = $booking->price_night * $booking->nights;
            // The stored total, the same figure the calendar and the owner portal show.
            // Recomputing it from price_night x nights understated August by RM354,563:
            // a monthly rental holds its contract value here against a nominal
            // nightly rate — 103.79 x 31 nights on a booking actually worth 38,036.
            $total = round((float) $booking->price, 2);
            $user = $booking->user;
            $listing = $booking->listing;
            if ($booking->source == 'Long Term Rental') {
                $exportData[$x] = [
                    $folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->cleaning_fee, $booking->sst_cf, $ota, $sst, $total, $booking->remark,
                ];
            } else {
                $exportData[$x] = [
                    $folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->cleaning_fee, $booking->sst_cf, $ota, $booking->sst, $total, $booking->remark,
                ];
            }

            $x++;
        }

        $sheet->fromArray(
            $exportData,
            null,
            'A1',
            true
        );
        $styleArrayFirstHeader = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'c65911',
                ],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font' => ['bold' => true, 'size' => 22],
        ];
        $styleArraySecondHeader = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'f4b084',
                ],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($styleArrayFirstHeader);
        $sheet->getStyle('A2:N2')->applyFromArray($styleArraySecondHeader);
        $alphabet = range('A', 'Z');
        for ($i = 0; $i <= 18; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($alphabet[$i])->setWidth(16);
        }

        ob_end_clean();
        $extension = 'Xlsx';
        $writer = IOFactory::createWriter($spreadsheet, $extension);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"Bookings.{$extension}\"");
        $writer->save('php://output');
        exit();
    }

    /**
     * Blank workbook carrying the columns importExcel expects.
     *
     * Defined next to the import so the two cannot drift. BookingImport reads
     * headings via WithHeadingRow and rejects a row outright when any of
     * listing_name, user_email, check_in, check_out or price_per_night is
     * missing, so those five are the ones that matter.
     */
    public function downloadBookingTemplate()
    {
        $columns = [
            'listing_name', 'user_email', 'check_in', 'check_out', 'price_per_night',
            'user_first_name', 'user_last_name', 'user_phone', 'adult', 'infant', 'remark',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($columns, null, 'A1');

        // A filled example row makes the expected date and number formats
        // obvious; it is deleted before importing.
        $sheet->fromArray([
            'Ekocheras J-28-09', 'guest@example.com', date('Y-m-d'),
            date('Y-m-d', strtotime('+2 days')), '150.00',
            'Jane', 'Tan', '0123456789', 2, 0, 'Optional note',
        ], null, 'A2');

        $last = 'K';
        foreach (range('A', $last) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="booking-import-template.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function exportExcelRange(Request $request)
    {
        if ($request->input('action') == "loaddata") {
            $id = $request->id;
            $userName = $request->user;
            $listingName = $request->listing;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $books = Booking::where('status', '>=', 3)->whereBetween('created_at', [$request->from_date, $request->to_date])->paginate(2000);

            $action = $request->action;
            return view('admin.listing.book.index', compact('books', 'action', 'id', 'userName', 'listingName', 'from_date', 'to_date'));
        }

        if ($request->input('action') == "loaddatacheckin") {
            $id = $request->id;
            $userName = $request->user;
            $listingName = $request->listing;
            $checkin_date = $request->checkin_date;
            $checkinto_date = $request->checkinto_date;
            $books = Booking::where('status', '>=', 3)->whereBetween('check_in', [$request->checkin_date, $request->checkinto_date])->paginate(2000);

            $action = $request->action;
            return view('admin.listing.book.index', compact('books', 'action', 'id', 'userName', 'listingName', 'checkin_date', 'checkinto_date'));
        }

        if ($request->input('action') == "exportreport") {
            // $bookings = Booking::where('status', '>=', 3)->whereBetween('created_at', [$request->from_date, $request->to_date])->get();
            // See exportExcel(): the archived scope blanked the listing name, and the
            // unfiltered query loaded every row at once.
            $bookings = Booking::where('status', '>=', 3)
                ->whereBetween('check_in', [$request->checkin_date, $request->checkinto_date])
                ->with(['user', 'listing', 'ezeeBooking'])
                ->lazyById(500);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->mergeCells('A1:S1');
            $exportData[0] = ['MOKA'];
            $exportData[1] = [
                'Booking Id', 'RES', 'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights', 'Reservation Source', 'Price per Night', 'Discount', 'Cleaning Fee', 'SST(CF)',
                'OTA', 'SST', 'Ezee Folio No', 'Total', 'Remarks',
            ];

            $x = 2;
            $sst = 0;
            foreach ($bookings as $booking) {
                $todays = $booking->created_at->format('Y-m-d');
                $ota_cal = (($booking->price_night * $booking->nights));
                $new_ota = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee;
                $new_ota1 = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee + $booking->sst + $booking->sst_cf;
                $ota = '';
                // Stored, not recalculated: the fee stamped when the booking was
                // assigned. Recomputing here applied today's rates to historical
                // bookings, so the same export run twice either side of a rate
                // change produced two different documents.
                $ota = $booking->ota_fee;
                $sst = $booking->sst; // stored figure: a tenancy carries its SST too
                $ezee = $booking->ezeeBooking;
                // Ground rule 12: eZee's source, mapped to the one channel list.
                $otaText = \App\Support\Channel::canonical($ezee->Source ?? $booking->source);

                // eZee's own folio number first (the one staff see in eZee), then the
                // folio typed at booking time, then MOKA's internal number.
                $ezee      = $booking->ezeeBooking;
                $ezeeFolio = $ezee->folio_no ?? '';
                if ($ezeeFolio === '' && !empty($booking->server_folio_no)) {
                    $ezeeFolio = $booking->server_folio_no;
                }
                $folio_no  = $ezeeFolio !== '' ? $ezeeFolio : $booking->folio_no;
                // The guest as eZee names them; hand-keyed rows sit under a staff account.
                $firstName = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) $ezee->FirstName) : ($booking->user->name ?? '');
                $lastName  = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) ($ezee->LastName ?? '')) : ($booking->user->last_name ?? '');
                $user = $booking->user;
                $listing = $booking->listing;
                // echo $booking->id."<br/>";
                $ezee = $booking->ezeeBooking;
                $total_charges = $booking->price_night * $booking->nights;
                // The stored total, the same figure the calendar and the owner portal show.
                // Recomputing it from price_night x nights understated August by RM354,563:
                // a monthly rental holds its contract value here against a nominal
                // nightly rate — 103.79 x 31 nights on a booking actually worth 38,036.
                $total = round((float) $booking->price, 2);
                if ($booking->source == 'Long Term Rental') {
                    $exportData[$x] = [
                        $booking->id, $ezee->SubBookingId ?? '', $folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                        $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $sst, $ezeeFolio, $total, $booking->remark,
                    ];
                } else {
                    $exportData[$x] = [
                        $booking->id, $ezee->SubBookingId ?? '', $folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                        $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $booking->sst, $ezeeFolio, $total, $booking->remark,
                    ];
                }

                $x++;
            }

            $sheet->fromArray(
                $exportData,
                null,
                'A1',
                true
            );
            $styleArrayFirstHeader = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'c65911',
                    ],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true, 'size' => 22],
            ];
            $styleArraySecondHeader = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'f4b084',
                    ],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:S1')->applyFromArray($styleArrayFirstHeader);
            $sheet->getStyle('A2:S2')->applyFromArray($styleArraySecondHeader);
            $alphabet = range('A', 'Z');
            for ($i = 0; $i <= 18; $i++) {
                $spreadsheet->getActiveSheet()->getColumnDimension($alphabet[$i])->setWidth(16);
            }

            ob_end_clean();
            $extension = 'Xlsx';
            $writer = IOFactory::createWriter($spreadsheet, $extension);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"Bookings.{$extension}\"");
            $writer->save('php://output');
            exit();
        }

        // See exportExcel(): archived listings blanked, and every row loaded at once.
        $bookings = Booking::where('status', '>=', 3)
            ->whereBetween('created_at', [$request->from_date, $request->to_date])
            ->with(['user', 'listing', 'ezeeBooking'])
            ->lazyById(500);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells('A1:S1');
        $exportData[0] = ['MOKA'];
        $exportData[1] = [
            'Booking Id', 'RES', 'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights', 'Reservation Source', 'Price per Night', 'Discount', 'Cleaning Fee', 'SST(CF)',
            'OTA', 'SST', 'Ezee Folio No', 'Total', 'Remarks',
        ];
        // $exportData[1]=['Folio No.','First Name','Last Name','Listing Name','Arrival','Departure','Nights','Reservation Source','Price per Night','Cleaning Fee',
        // 'OTA','SST','Total','Remarks'];
        $x = 2;
        $sst = 0;
        foreach ($bookings as $booking) {
            $todays = $booking->created_at->format('Y-m-d');
            $ota_cal = (($booking->price_night * $booking->nights));
            $new_ota = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee;
            $new_ota1 = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee + $booking->sst + $booking->sst_cf;
            $ota = '';
            // Stored, not recalculated: the fee stamped when the booking was
            // assigned. Recomputing here applied today's rates to historical
            // bookings, so the same export run twice either side of a rate
            // change produced two different documents.
            $ota = $booking->ota_fee;
            $sst = $booking->sst; // stored figure: a tenancy carries its SST too

            $otaText = '';
            if ($booking->source == 'Booking') {
                    $otaText = 'Booking.com';
                } elseif (preg_match('/^(pms|walk|google|book on google|internet|booking engine|website)/i', trim((string) $booking->source))) {
                    // Direct channels: PMS, walk-in, Google, Internet Booking Engine.
                    // Finance treats them as one channel, "Website", at the 8% fee.
                    $otaText = 'Website';
                } else {
                    $otaText = $booking->source;
                }

                // eZee's own folio number first (the one staff see in eZee), then the
            // folio typed at booking time, then MOKA's internal number.
            $ezee      = $booking->ezeeBooking;
            $ezeeFolio = $ezee->folio_no ?? '';
            if ($ezeeFolio === '' && !empty($booking->server_folio_no)) {
                $ezeeFolio = $booking->server_folio_no;
            }
            $folio_no  = $ezeeFolio !== '' ? $ezeeFolio : $booking->folio_no;
            // The guest as eZee names them; hand-keyed rows sit under a staff account.
            $firstName = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) $ezee->FirstName) : ($booking->user->name ?? '');
            $lastName  = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) ($ezee->LastName ?? '')) : ($booking->user->last_name ?? '');
                // eZee's own folio number first (the one staff see in eZee), then the
            // folio typed at booking time, then MOKA's internal number.
            $ezee      = $booking->ezeeBooking;
            $ezeeFolio = $ezee->folio_no ?? '';
            if ($ezeeFolio === '' && !empty($booking->server_folio_no)) {
                $ezeeFolio = $booking->server_folio_no;
            }
            $folio_no  = $ezeeFolio !== '' ? $ezeeFolio : $booking->folio_no;
            // The guest as eZee names them; hand-keyed rows sit under a staff account.
            $firstName = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) $ezee->FirstName) : ($booking->user->name ?? '');
            $lastName  = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) ($ezee->LastName ?? '')) : ($booking->user->last_name ?? '');
            $user = $booking->user;
            $listing = $booking->listing;
            $ezee = $booking->ezeeBooking;
            $total_charges = $booking->price_night * $booking->nights;
            // The stored total, the same figure the calendar and the owner portal show.
            // Recomputing it from price_night x nights understated August by RM354,563:
            // a monthly rental holds its contract value here against a nominal
            // nightly rate — 103.79 x 31 nights on a booking actually worth 38,036.
            $total = round((float) $booking->price, 2);
            if ($booking->source == 'Long Term Rental') {
                $exportData[$x] = [
                    $booking->id, $ezee->SubBookingId ?? '', $folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $sst, $ezeeFolio, $total, $booking->remark,
                ];
            } else {
                $exportData[$x] = [
                    $booking->id, $ezee->SubBookingId ?? '', $folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $booking->sst, $ezeeFolio, $total, $booking->remark,
                ];
            }

            // $exportData[$x] = [$booking->folio_no, $firstName, $lastName, $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
            // $booking->source, $booking->price_night, $booking->cleaning_fee, $booking->ota_fee, $booking->sst, $booking->price, $booking->remark];
            $x++;
        }

        $sheet->fromArray(
            $exportData,
            null,
            'A1',
            true
        );
        $styleArrayFirstHeader = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'c65911',
                ],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'font' => ['bold' => true, 'size' => 22],
        ];
        $styleArraySecondHeader = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'f4b084',
                ],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($styleArrayFirstHeader);
        $sheet->getStyle('A2:Q2')->applyFromArray($styleArraySecondHeader);
        $alphabet = range('A', 'Z');
        for ($i = 0; $i <= 18; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($alphabet[$i])->setWidth(16);
        }

        ob_end_clean();
        $extension = 'Xlsx';
        $writer = IOFactory::createWriter($spreadsheet, $extension);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"Bookings.{$extension}\"");
        $writer->save('php://output');
        exit();
    }
}
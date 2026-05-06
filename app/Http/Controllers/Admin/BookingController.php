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
        // dd("ok");
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
                                                    'TransactionId' => $transaction_id,
                                                    'IsConfirmed' => $is_confirmed,
                                                    'RateplanName' => $rateplanName,
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
                                                        'TransactionId' => $transaction_id,
                                                        'IsConfirmed' => $is_confirmed,
                                                        'RateplanName' => $rateplanName,
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
    
    // Only get minimal data for the page (modals, etc.)
    $books = Booking::with('ezeeBooking')
        ->where('status', '>=', 3)
        ->limit(20) // Just enough for any modals on the page
        ->get();

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
        'checkinto_date'
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
        // dd($request->sst_cf);
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
        // dd("oo");
        $bookings = Booking::where('status', '>=', 3)->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells('A1:N1');
        $exportData[0] = ['MOKA'];
        $exportData[1] = [
            'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights', 'Reservation Source', 'Price per Night', 'Cleaning Fee', 'SST(CF)',
            'OTA', 'SST', 'Total', 'Remarks',
        ];
        $chackdate = '2022-11-30';
        $chackdate15 = '2023-02-01';
        $chackdatenew = '2023-06-17';
        $chackdatenew8 = '2023-07-01';
        $sep = '2024-09-01';
        $percentage = 20;
        $percentageTraveloka = 17;
        $percentageAirbnb = 15.9;
        $percentageAirbnb_sep = 15;
        $percentageBooking1 = 18;
        $percentageBooking2 = 2.8;
        $percentage_walkIn = 12;
        $percentage_walkIn8 = 8;
        $tiket = 17;
        $x = 2;
        $sst = 0;
        foreach ($bookings as $booking) {
            $todays = $booking->check_in;
            $ota_cal = (($booking->price_night * $booking->nights));
            $new_ota = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee;
            $new_ota1 = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee + $booking->sst + $booking->sst_cf;
            $ota = '';
            if ($booking->source == 'Airbnb') {

                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays < $sep) {
                    $ota = round((($percentageAirbnb / 100) * $new_ota), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $sep) {
                    $ota = round((($percentageAirbnb_sep / 100) * $new_ota1), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Booking.com' || $booking->source == 'Booking') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                    $ota1 = round((($percentageBooking1 / 100) * $new_ota), 2);
                    $ota2 = round((($percentageBooking2 / 100) * $new_ota1), 2);
                    $ota = $ota1 + $ota2;
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Walk-in' || $booking->source == 'PMS' || $booking->source == 'Walk In' || $booking->source == 'Website') {
                if ($todays > $chackdate && $todays <= $chackdate15) {

                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays > $chackdate15 && $todays < $chackdatenew8) {
                    $ota = round((($percentage_walkIn / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays > $chackdate15 && $todays >= $chackdatenew8) {
                    $ota = round((($percentage_walkIn8 / 100) * $new_ota), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Agoda') {
                if ($todays > $chackdate && $todays < $chackdatenew && $todays < $chackdatenew8) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $chackdatenew8) {
                    $ota = "0" ?? 0.00;
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Traveloka') {
                if ($todays > $chackdate && $todays < $chackdatenew && $todays < $chackdatenew8) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $chackdatenew8) {
                    $ota = round((($percentageTraveloka / 100) * $new_ota1), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } else if ($booking->source == 'Trip.com' || $booking->source == 'Ctrip.com' || $booking->source == 'CTrip.com' || $booking->source == 'CTrip' || $booking->source == 'Ctrip') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                    $ota = "0" ?? 0.00;
                } else {
                    $ota = $booking->ota_fee;
                }
                // dd($ota);
            } elseif ($booking->source == 'Owner' || $booking->source == 'owner') {
                $ota = number_format(0.00, 2);
            } elseif ($booking->source == 'Long Term Rental') {
                $ota = number_format(0.00, 2);
                $sst = 0;
            } elseif ($booking->source == 'Tiket.com') {
                $ota = 0;

            } elseif ($booking->source == 'Expedia') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                    $ota = round((($percentage / 100) * $new_ota1), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } else {

                if ($todays > $chackdate) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            }

            $otaText = '';
            if ($booking->source == 'Booking') {
                $otaText = 'Booking.com';
            } else if ($booking->source == 'PMS' || $booking->source == 'Walk-in' || $booking->source == 'Walk In') {
                $otaText = 'Website';
            } else {
                $otaText = $booking->source;
            }

            if (!empty($booking->server_folio_no)) {
                $folio_no = $booking->server_folio_no;
            } else {
                $folio_no = $booking->folio_no;
            }

            $total_charges = $booking->price_night * $booking->nights;
            $total = round(($total_charges + $booking->cleaning_fee + $booking->sst_cf + $booking->sst) - $booking->discount_fee, 2);
            $user = User::find($booking->user_id);
            $listing = Listing::find($booking->listing_id);
            if ($booking->source == 'Long Term Rental') {
                $exportData[$x] = [
                    $folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->cleaning_fee, $booking->sst_cf, $ota, $sst, $total, $booking->remark,
                ];
            } else {
                $exportData[$x] = [
                    $folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
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
        for ($i = 0; $i <= 13; $i++) {
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
            $bookings = Booking::where('status', '>=', 3)->whereBetween('check_in', [$request->checkin_date, $request->checkinto_date])->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->mergeCells('A1:R1');
            $exportData[0] = ['MOKA'];
            $exportData[1] = [
                'Booking Id', 'RES', 'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights', 'Reservation Source', 'Price per Night', 'Discount', 'Cleaning Fee', 'SST(CF)',
                'OTA', 'SST', 'Ezee Folio No', 'Total', 'Remarks',
            ];

            $chackdate = '2022-11-30';
            $chackdate15 = '2023-02-01';
            $chackdatenew = '2023-06-17';
            $chackdatenew8 = '2023-07-01';
            $sep = '2024-09-01';
            $percentage = 20;
            $percentageTraveloka = 17;
            $percentageAirbnb = 15.9;
            $percentageAirbnb_sep = 15;
            $percentageBooking1 = 18;
            $percentageBooking2 = 2.8;
            $percentage_walkIn = 12;
            $percentage_walkIn8 = 8;
            $tiket = 17;
            $x = 2;
            $sst = 0;
            foreach ($bookings as $booking) {
                $todays = $booking->created_at->format('Y-m-d');
                $ota_cal = (($booking->price_night * $booking->nights));
                $new_ota = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee;
                $new_ota1 = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee + $booking->sst + $booking->sst_cf;
                $ota = '';
                if ($booking->source == 'Airbnb') {

                    if ($todays > $chackdate && $todays < $chackdatenew) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays < $sep) {
                        $ota = round((($percentageAirbnb / 100) * $new_ota), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $sep) {
                        $ota = round((($percentageAirbnb_sep / 100) * $new_ota1), 2);
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } elseif ($booking->source == 'Booking.com' || $booking->source == 'Booking') {
                    if ($todays > $chackdate && $todays < $chackdatenew) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                        $ota1 = round((($percentageBooking1 / 100) * $new_ota), 2);
                        $ota2 = round((($percentageBooking2 / 100) * $new_ota1), 2);
                        $ota = $ota1 + $ota2;
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } elseif ($booking->source == 'Walk-in' || $booking->source == 'PMS' || $booking->source == 'Walk In' || $booking->source == 'Website') {
                    if ($todays > $chackdate && $todays <= $chackdate15) {

                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays > $chackdate15 && $todays < $chackdatenew8) {
                        $ota = round((($percentage_walkIn / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays > $chackdate15 && $todays >= $chackdatenew8) {
                        $ota = round((($percentage_walkIn8 / 100) * $new_ota), 2);
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } elseif ($booking->source == 'Agoda') {
                    if ($todays > $chackdate && $todays < $chackdatenew && $todays < $chackdatenew8) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $chackdatenew8) {
                        $ota = "0" ?? 0.00;
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } elseif ($booking->source == 'Traveloka') {
                    if ($todays > $chackdate && $todays < $chackdatenew) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                        $ota = round((($percentageTraveloka / 100) * $new_ota1), 2);
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } elseif ($booking->source == 'Trip.com' || $booking->source == 'CTrip.com' || $booking->source == 'Ctrip' || $booking->source == 'CTrip' || $booking->source == 'Ctrip') {
                    if ($todays > $chackdate && $todays < $chackdatenew) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                        $ota = 0.00;
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } elseif ($booking->source == 'Owner' || $booking->source == 'owner') {
                    $ota = number_format(0.00, 2);
                } elseif ($booking->source == 'Tiket.com') {
                    $ota = 0;

                } elseif ($booking->source == 'Long Term Rental') {
                    $ota = number_format(0.00, 2);
                    $sst = 0;
                } elseif ($booking->source == 'Expedia') {
                    if ($todays > $chackdate && $todays < $chackdatenew) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                        $ota = round((($percentage / 100) * $new_ota1), 2);
                    } else {
                        $ota = $booking->ota_fee;
                    }
                } else {

                    if ($todays > $chackdate) {
                        $ota = round((($percentage / 100) * $ota_cal), 2);
                    } else {
                        $ota = $booking->ota_fee;
                    }
                }
                $otaText = '';
                if ($booking->source == 'Booking') {
                    $otaText = 'Booking.com';
                } else if ($booking->source == 'PMS' || $booking->source == 'Walk-in' || $booking->source == 'Walk In') {
                    $otaText = 'Website';
                } else {
                    $otaText = $booking->source;
                }

                if (!empty($booking->server_folio_no)) {
                    $folio_no = $booking->server_folio_no;
                } else {
                    $folio_no = $booking->folio_no;
                }
                $user = User::find($booking->user_id);
                $listing = Listing::find($booking->listing_id);
                // echo $booking->id."<br/>";
                $ezee = EzeeBooking::where('book_id', $booking->id)->first();
                $total_charges = $booking->price_night * $booking->nights;
                $total = round(($total_charges + $booking->cleaning_fee + $booking->sst_cf + $booking->sst) - $booking->discount_fee, 2);
                if ($booking->source == 'Long Term Rental') {
                    $exportData[$x] = [
                        $booking->id, $ezee->SubBookingId ?? '', $folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                        $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $sst, $booking->server_folio_no, $total, $booking->remark,
                    ];
                } else {
                    $exportData[$x] = [
                        $booking->id, $ezee->SubBookingId ?? '', $folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                        $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $booking->sst, $booking->server_folio_no, $total, $booking->remark,
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
            $sheet->getStyle('A1:R1')->applyFromArray($styleArrayFirstHeader);
            $sheet->getStyle('A2:R2')->applyFromArray($styleArraySecondHeader);
            $alphabet = range('A', 'Z');
            for ($i = 0; $i <= 13; $i++) {
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

        $bookings = Booking::where('status', '>=', 3)->whereBetween('created_at', [$request->from_date, $request->to_date])->get();

        // dd($bookings);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells('A1:R1');
        $exportData[0] = ['MOKA'];
        $exportData[1] = [
            'Booking Id', 'RES', 'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights', 'Reservation Source', 'Price per Night', 'Discount', 'Cleaning Fee', 'SST(CF)',
            'OTA', 'SST', 'Ezee Folio No', 'Total', 'Remarks',
        ];
        // $exportData[1]=['Folio No.','First Name','Last Name','Listing Name','Arrival','Departure','Nights','Reservation Source','Price per Night','Cleaning Fee',
        // 'OTA','SST','Total','Remarks'];
        $chackdate = '2022-11-30';
        $chackdate15 = '2023-02-01';
        $chackdatenew = '2023-06-17';
        $chackdatenew8 = '2023-07-01';
        $sep = '2024-09-01';
        $percentage = 20;
        $percentageTraveloka = 17;
        $percentageTraveloka_sep = 15;
        $percentageAirbnb = 15.9;
        $percentageAirbnb_sep = 15.9;
        $percentageBooking1 = 18;
        $percentageBooking2 = 2.8;
        $percentage_walkIn = 12;
        $percentage_walkIn8 = 8;
        $tiket = 17;
        $x = 2;
        $sst = 0;
        foreach ($bookings as $booking) {
            $todays = $booking->created_at->format('Y-m-d');
            $ota_cal = (($booking->price_night * $booking->nights));
            $new_ota = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee;
            $new_ota1 = (($booking->price_night * $booking->nights)) + $booking->cleaning_fee + $booking->sst + $booking->sst_cf;
            $ota = '';
            if ($booking->source == 'Airbnb') {

                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays < $sep) {
                    $ota = round((($percentageAirbnb / 100) * $new_ota), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $sep) {
                    $ota = round((($percentageAirbnb_sep / 100) * $new_ota), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Booking.com' || $booking->source == 'Booking') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                    $ota1 = round((($percentageBooking1 / 100) * $new_ota), 2);
                    $ota2 = round((($percentageBooking2 / 100) * $new_ota1), 2);
                    $ota = $ota1 + $ota2;
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Walk-in' || $booking->source == 'PMS' || $booking->source == 'Walk In' || $booking->source == 'Website') {
                if ($todays > $chackdate && $todays <= $chackdate15) {

                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays > $chackdate15 && $todays < $chackdatenew8) {
                    $ota = round((($percentage_walkIn / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays > $chackdate15 && $todays >= $chackdatenew8) {
                    $ota = round((($percentage_walkIn8 / 100) * $new_ota), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Agoda') {
                if ($todays > $chackdate && $todays < $chackdatenew && $todays < $chackdatenew8) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew && $todays >= $chackdatenew8) {
                    $ota = "0" ?? 0.00;
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Traveloka') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                    $ota = round((($percentageTraveloka / 100) * $new_ota1), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } elseif ($booking->source == 'Trip.com' || $booking->source == 'CTrip.com' || $booking->source == 'Ctrip.com' || $booking->source == 'Ctrip' || $booking->source == 'CTrip') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays > $chackdatenew) {
                    $ota = 0.00;
                } else {
                    $ota = $booking->ota_fee;
                }
            }  elseif ($booking->source == 'Owner' || $booking->source == 'owner') {
                $ota = number_format(0.00, 2);
            } elseif ($booking->source == 'Long Term Rental') {
                $ota = number_format(0.00, 2);
                $sst = 0;
            } elseif ($booking->source == 'Tiket.com') {
                $ota = 0;

            } elseif ($booking->source == 'Expedia') {
                if ($todays > $chackdate && $todays < $chackdatenew) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } elseif ($todays > $chackdate && $todays >= $chackdatenew) {
                    $ota = round((($percentage / 100) * $new_ota1), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            } else {

                if ($todays > $chackdate) {
                    $ota = round((($percentage / 100) * $ota_cal), 2);
                } else {
                    $ota = $booking->ota_fee;
                }
            }

            $otaText = '';
            if ($booking->source == 'Booking') {
                $otaText = 'Booking.com';
            } else if ($booking->source == 'PMS' || $booking->source == 'Walk-in' || $booking->source == 'Walk In') {
                $otaText = 'Website';
            } else {
                $otaText = $booking->source;
            }

            if (!empty($booking->server_folio_no)) {
                $folio_no = $booking->server_folio_no;
            } else {
                $folio_no = $booking->folio_no;
            }
            if (!empty($booking->server_folio_no)) {
                $folio_no = $booking->server_folio_no;
            } else {
                $folio_no = $booking->folio_no;
            }
            $user = User::find($booking->user_id);
            $listing = Listing::find($booking->listing_id);
            $ezee = EzeeBooking::where('book_id', $booking->id)->first();
            $total_charges = $booking->price_night * $booking->nights;
            $total = round(($total_charges + $booking->cleaning_fee + $booking->sst_cf + $booking->sst) - $booking->discount_fee, 2);
            if ($booking->source == 'Long Term Rental') {
                $exportData[$x] = [
                    $booking->id, $ezee->SubBookingId ?? '', $folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $sst, $booking->server_folio_no, $total, $booking->remark,
                ];
            } else {
                $exportData[$x] = [
                    $booking->id, $ezee->SubBookingId ?? '', $folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
                    $otaText, $booking->price_night, $booking->discount_fee, $booking->cleaning_fee, $booking->sst_cf, $ota, $booking->sst, $booking->server_folio_no, $total, $booking->remark,
                ];
            }

            // $exportData[$x] = [$booking->folio_no, $user->name ?? '', $user->last_name ?? '', $listing->name ?? '', $booking->check_in, $booking->check_out, $booking->nights,
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
        for ($i = 0; $i <= 13; $i++) {
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
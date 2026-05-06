<?php

namespace App\Http\Controllers\Owner;

use App\Export\OwnerListingExport;
use App\Http\Controllers\Controller;
use App\Listing;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $listings = Listing::where('user_id', Auth::user()->id)->get();
        return view('owner.listing.index', compact('listings'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
    {
        $listingData = Listing::where('user_id', Auth::id())->where('status', 1)->first();
        $id = $listingData->id ?? '';
        if (empty($id)) {
            $listing = Listing::where('user_id', Auth::id())->where('status', 1)->first();
            $id = $listing->id ?? '';
        } else {
            $listing = Listing::where('user_id', Auth::id())->find($id);
            if (empty($listing)) {
                $listing = Listing::where('user_id', Auth::id())->find($listingData->id);
            }
        }
        // dd($listing);
        $allListings = Listing::where('user_id', Auth::id())->where('status', 1)->get();
        // dd(Auth::id());
        $selDate = $request->date;
        if (empty($selDate)) {
            $selDate = Carbon::now();
        } else {
            $selDate = date_create($request->date);
        }
        $thisMonth = date_format($selDate, 'Y-m-01');
        $nextMonth3 = date_create($thisMonth)->modify('+1 month');
        $endOfMonth = date_format($nextMonth3, 'Y-m-01');
        $next2Months = date_create($thisMonth)->modify('+2 month');
        $FirstOf2MLater = date_format($next2Months, 'Y-m-01');

        $nextMonth6 = date_create($thisMonth)->modify('+1 month');
        $prevMonth5 = date_create($thisMonth)->modify('-6 months');
        $nextOf6Month = date_format($nextMonth6, 'Y-m-01');
        $prevOf6Month = date_format($prevMonth5, 'Y-m-01');

        $graphdata = DB::table('bookings')
            ->select(DB::raw('DATE_FORMAT(check_in, "%m") as month , DATE_FORMAT(check_in, "%Y") as year'))
            ->where('listing_id', $id)
            ->where('check_in', '>=', $prevOf6Month)
            ->where('check_in', '<', $nextOf6Month)
            ->where('status', '>=', 5)
            ->orderBy('check_in', 'ASC')
            ->groupBy('month', 'year')
            ->get();

        $arrayCheckIn = [];
        $arrayCheckOut = [];
        foreach ($graphdata as $bookg) {
            $new = "$bookg->year-$bookg->month-01";
            $from = date_create("$bookg->year-$bookg->month-01")->modify('+1 month');
            $to = date_format($from, 'Y-m-01');
            array_push($arrayCheckIn, $new);
            array_push($arrayCheckOut, $to);
        }
        $data = array_combine($arrayCheckIn, $arrayCheckOut);
//    dd($data);
        $mobile = [];
        $graphArray = [];
        $graphavg = [];
        foreach ($data as $start => $end) {
            $revenue = 0;
            $bookingThisMonth = 0;
            $monthCreated = 0;
            $bookedDays = 0;
            $bookedDays2 = 0;
            $Occupancy = 0;
            $books = \App\Booking::where('listing_id', $id)->where([['status', '>=', 5], ['check_out', '>', $start], ['check_in', '<=', $end]])->orderBy('check_in', 'ASC')->get();
            foreach ($books as $book) {

                $format_date = date_create($start);
                $months = date_format($format_date, 'M');
                $years = date_format($format_date, 'y');
                $endOfNextMonth = date_create($end)->modify('+1 month');
                $endOfNextMonthFormatted = date_format($endOfNextMonth, 'Y-m-t');
                $firstOfNextMonth = date_format($endOfNextMonth, 'Y-m-01');

                $checkIn = $book->check_in;
                $checkOut = $book->check_out;
                $nights = $book->nights;
                if ($book->check_in >= $start && $book->check_in <= $end) {
                    if ($book->check_out <= $end) {
                        $nights = $book->nights;
                    } else {
                        $earlier = new DateTime($end);
                        $later = new DateTime($book->check_out);
                        $diff = $later->diff($earlier)->format("%a");
                        $nights = $book->nights - $diff;
                        $checkOut = $end;
//            dd($nights);
                    }

                } elseif ($book->check_out > $start && $book->check_out <= $end) {
                    $earlier = new DateTime($book->check_in);
                    $later = new DateTime($start);
                    $diff = $later->diff($earlier)->format("%a");
                    $nights = $book->nights - $diff;
                    $checkIn = $start;
                } elseif ($book->check_in <= $start && $book->check_out >= $end) {
                    $nights = date_format($selDate, 't');
                    $checkIn = $start;
                    $checkOut = $end;
                } elseif ($book->check_in >= $firstOfNextMonth && $book->check_in <= $endOfNextMonthFormatted) {
                    if ($book->check_out <= $endOfNextMonthFormatted) {
                        $bookedDays2 = $bookedDays2 + $book->nights;
                    } else {
                        $earlier = new DateTime($endOfNextMonthFormatted);
                        $later = new DateTime($book->check_out);
                        $diff = $later->diff($earlier)->format("%a");
                        $bookedDays2 = $bookedDays2 + $book->nights - $diff;
                    }
                } elseif ($book->check_out >= $firstOfNextMonth && $book->check_out <= $endOfNextMonthFormatted) {
                    $earlier = new DateTime($book->check_in);
                    $later = new DateTime($firstOfNextMonth);
                    $diff = $later->diff($earlier)->format("%a");
                    $bookedDays2 = $bookedDays2 + $book->nights - $diff;
                }

                if ($checkIn != $checkOut && $checkOut <= $end) {
//        echo round($nights*$book->price_night, 2).'<br />';
                    $revenue = $revenue + round($nights * $book->price_night, 2);
                    $bookingThisMonth++;
                    $bookedDays = $bookedDays + $nights;
                }
            }
            $bookedDays2 = $bookedDays2 + $bookedDays;
            $averageRentalRateDays = $bookedDays;
            if (empty($request->date)) {
                $Occupancy = round((($bookedDays / date_format($format_date, 't')) * 100), 2);
            } else {
                $Occupancy = round((($bookedDays / date_format($format_date, 't')) * 100), 2);
            }

            $mobile_chart = [$months, (($book->price_night) * $nights + $book->cleaning_fee), (($book->price_night) * $nights), (($book->price_night) * $nights), ($book->price_night + ($book->price_night * $nights))];
            // dd($mobile_chart);
            $occupancyRateThis = $bookedDays / date_format($selDate, 't');
            if ($listing->type == 'group') {
                $bookingThisMonth = 0;
                $nights = 0;
                $bookedDays = 0;
                $revenue = 0;
                $totalNights = 0;
                $group = \App\ListingGroup::where('listing_id', $id)->first();
                //   dd($data);
                if (!empty($group)) {
                    $listingIds = \App\ListingGroup::where('group_id', $group->group_id)->pluck('listing_id')->toArray();
                    $revenues = \App\Booking::whereIn('listing_id', $listingIds)->where([['status', '>=', 5], ['check_out', '>', $start], ['check_in', '<=', $end]])->get();
                    foreach ($revenues as $book) {

                        $checkIn = $book->check_in;
                        $checkOut = $book->check_out;
                        $nights = $book->nights;
                        if ($book->check_in >= $start && $book->check_in <= $end) {
                            if ($book->check_out <= $end) {
                                $nights = $book->nights;
                            } else {
                                $earlier = new DateTime($end);
                                $later = new DateTime($book->check_out);
                                $diff = $later->diff($earlier)->format("%a");
                                $nights = $book->nights - $diff;
                                $checkOut = $end;
                            }

                        } elseif ($book->check_out > $start && $book->check_out <= $end) {
                            $earlier = new DateTime($book->check_in);
                            $later = new DateTime($thisMonth);
                            $diff = $later->diff($earlier)->format("%a");
                            $nights = $book->nights - $diff;
                            $checkIn = $start;
                        } elseif ($book->check_in <= $start && $book->check_out >= $end) {
                            $nights = date_format($selDate, 't');
                            $checkIn = $start;
                            $checkOut = $end;
                        }

                        if ($checkIn != $checkOut && $checkOut <= $end) {
                            $revenue = $revenue + ($nights * $book->price_night) + $book->cleaning_fee + $book->sst;
                            $bookingThisMonth++;
                            $bookedDays = $bookedDays + $nights;
                        }
                    }
                    $averageRentalRateDays = $bookedDays;
                    $bookedDays = $bookedDays / count($listingIds);
                    $averageBookedDate = $bookedDays;
                    $bookedInNext60 = \App\Booking::whereIn('listing_id', $listingIds)->where([['status', '>=', 5], ['check_in', '>=', $start], ['check_out', '<=', $FirstOf2MLater]])->sum('nights');
                    $bookedInNext60 = round($bookedInNext60 / count($listingIds));
                    $Occupancy = round((($bookedDays / date_format($selDate, 't')) * 100), 2);
                    $occupancyRateThis = round(($occupancyRateThis / count($listingIds)) * 100, 2);
                }
            } else {
                //    $revenue = $books->sum('price');
                if ($monthCreated > 0) {$averageBookedDate = round($totalNights / $monthCreated);}
                $occupancyRateThis = round($occupancyRateThis * 100, 2);
            }
            //exit;
            if ($bookedDays > 0) {$averageRentalRate = round(($revenue / $averageRentalRateDays), 2);}
            $occupancyRateOther = round((($bookedDays / date_format($selDate, 't')) * 100), 2);

            $graphData = [$months . "`" . $years, $Occupancy];
            $graphavgs = [$months . "`" . $years, $averageRentalRate];

            array_push($mobile, $mobile_chart);
            array_push($graphArray, $graphData);
            array_push($graphavg, $graphavgs);
            // echo "<pre>";
            // print_r($mobile);
            // echo "<pre>";
        }

        $graphO = DB::table('bookings')
            ->select(DB::raw('DATE_FORMAT(check_in, "%b") as month, DAY(LAST_DAY(DATE_FORMAT(check_in,"%Y-%m-%d"))) as days ,DATE_FORMAT(check_in, "%y") as year,DATEDIFF(check_out, check_in) AS daydiff'))
        // ->where('user_id', Auth::id())
            ->where('check_in', '>', $prevOf6Month)
            ->where('check_out', '<=', $nextOf6Month)
            ->where('status', '>=', 5)
            ->orderBy('check_in', 'ASC')
            ->groupBy('month')
            ->get();
        $graphmonthO = json_decode(json_encode($graphO), true);

        $graph = DB::table('bookings')
            ->select(DB::raw('DATE_FORMAT(check_in, "%b") as month,DATE_FORMAT(check_in, "%y") as year,price_night,nights,cleaning_fee , DATEDIFF(check_out, check_in) AS days'))
            ->where('check_in', '>', $prevOf6Month)
            ->where('check_out', '<=', $nextOf6Month)
        //  ->whereIn('listing_id', $listingIds)
            ->where('status', '>=', 5)
            ->orderBy('check_in', 'ASC')
            ->groupBy('month')
            ->get();
        //  dd($graph);
        $sub_month = json_encode($graph, true);
        $graphmonth = json_decode(json_encode($graph), true);

        $testing = $request->testing;
        return view('owner.listing.report', compact('id', 'graphArray', 'graphavg', 'listing', 'allListings', 'selDate', 'testing', 'graphmonthO', 'graphmonth'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        return Excel::download(new OwnerListingExport(), 'listing.xlsx');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function performance()
    {
        return view('owner.listing.performance');
    }
}

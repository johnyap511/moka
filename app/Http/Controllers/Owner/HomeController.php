<?php

namespace App\Http\Controllers\Owner;

use App\Booking;
use App\Http\Controllers\Controller;
use App\Listing;
use App\User;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexcopy(Request $request)
    {
        $id = $request->listing_id;
        $authId = Auth::user()->id;
        if (empty($id)) {
            $listing = Listing::where('user_id', $authId)->first();
            $id = $listing->id ?? '';
        } else {
            $listing = Listing::find($id);
            if ($listing->user_id != $authId) {
                return back()->with('error', 'Sorry you are not permitted to access this listing!');
            }
        }
        $allListings = Listing::where('user_id', $authId)->get();
        $selDate = $request->date;
        if (empty($selDate)) {
            $selDate = Carbon::now();
        } else {
            $selDate = date_create($request->date);
        }

        return view('owner.home.index', compact('listing', 'id', 'allListings', 'selDate'));
    }

    public function index(Request $request)
    {
        $listingIds = Listing::where('user_id', Auth::user()->id)->pluck('id')->toArray();
        $selDate = $request->date ?? Carbon::now();
        $id = $request->listing_id;
        $authId = Auth::user()->id;
        if (empty($id)) {
            $listing = Listing::where('user_id', $authId)->where('status', 1)->first();
            $id = $listing->id ?? '';
            if (empty($id)) {

                $revenueyearly = 0;
                $allListings = Listing::where('user_id', $authId)->pluck('id')->toArray();
                if (empty($selDate)) {
                    $selDate = Carbon::now();
                } else {
                    $selDate = date_create($request->date);
                }
                // $thisMonth1 = date_create($selDate);
                $thisMonth = date_format($selDate, 'Y-m-01');
                $nextMonth3 = date_create($thisMonth)->modify('+1 month');
                $endOfMonth = date_format($nextMonth3, 'Y-m-01');

                $nextMonth6 = date_create($thisMonth)->modify('+1 month');
                $prevMonth5 = date_create($thisMonth)->modify('-2 months');
                $nextOf6Month = date_format($nextMonth6, 'Y-m-01');
                $prevOf6Month = date_format($prevMonth5, 'Y-m-01');
                $graphArray = [];
                $graphavg = [];
                $mobile = [];
                $thisMonth_today = Carbon::now();
                $year_start = date_format($thisMonth_today, 'Y-01-01');
                $books = \App\Booking::where([['status', '>=', 5]])
                    ->whereIn('listing_id', $allListings)
                    ->where('check_out', '>', $thisMonth)
                    ->where('check_in', '<=', $nextOf6Month)
                    ->orderBy('check_in', 'ASC')
                    ->get();
                $listing = Listing::find($id);
                return view('owner.home.index', compact('allListings', 'listing', 'listingIds', 'id', 'books', 'selDate', 'year_start', 'revenueyearly', 'graphavg', 'graphArray', 'mobile'));
                // compact('listing', 'id', 'allListings', 'selDate', 'revenueyearly', 'graphArray', 'graphavg', 'mobile', 'year_start')
            }
        } else {
            $listing = Listing::find($id);
            if ($listing->user_id != $authId) {
                return back()->with('error', 'Sorry you are not permitted to access this listing!');
            }
        }
        $allListings = Listing::where('user_id', $authId)->where('status', 1)->get();

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
        $totalNights = 0;
        $graphdata = DB::table('bookings')
            ->select(DB::raw("strftime('%m', check_in) as month, strftime('%Y', check_in) as year"))
            ->where('listing_id', $request->listing_id ?? $id)
            ->where('check_in', '>=', $prevOf6Month)
            ->where('check_in', '<', $nextOf6Month)
            ->where('status', '>=', 5)
            ->orderBy('check_in', 'ASC')
            ->groupBy('check_in')
            ->get();

        $arrayCheckIn = [];
        $arrayCheckOut = [];
        $revenueyearly = 0;
        $revenue = 0;
        foreach ($graphdata as $bookg) {
            $new = "$bookg->year-$bookg->month-01";
            $from = date_create("$bookg->year-$bookg->month-01")->modify('+1 month');
            $to = date_format($from, 'Y-m-01');
            array_push($arrayCheckIn, $new);
            array_push($arrayCheckOut, $to);
        }
        $data = array_combine($arrayCheckIn, $arrayCheckOut);
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
            $books = \App\Booking::where('listing_id', $request->listing_id ?? $id)->where([['status', '>=', 5], ['check_out', '>', $start], ['check_in', '<=', $end]])->orderBy('check_in', 'ASC')->get();
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

            $occupancyRateThis = $bookedDays / date_format($selDate, 't');
            if ($listing->type == 'group') {
                $bookingThisMonth = 0;
                $nights = 0;
                $bookedDays = 0;
                $revenue = 0;
                $group = \App\ListingGroup::where('listing_id', $id)->first();

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
                    $bookedInNext60 = \App\Booking::whereIn('listing_id', $listingIds)->where([['status', '>=', 5], ['check_in', '>=', $start], ['check_out', '<=', $end]])->sum('nights');
                    $bookedInNext60 = round($bookedInNext60 / count($listingIds));
                    $Occupancy = round((($bookedDays / date_format($selDate, 't')) * 100), 2);
                    $occupancyRateThis = round(($occupancyRateThis / count($listingIds)) * 100, 2);
                }
            } else {

                if ($monthCreated > 0) {
                    $averageBookedDate = round($totalNights / $monthCreated);
                }
                $occupancyRateThis = round($occupancyRateThis * 100, 2);
            }

            if ($bookedDays > 0) {
                $averageRentalRate = round(($revenue / $averageRentalRateDays), 2);
            }
            $occupancyRateOther = round((($bookedDays / date_format($selDate, 't')) * 100), 2);

            $graphData = [$months . "`" . $years, $Occupancy];
            $graphavgs = [$months . "`" . $years, $averageRentalRate];

            array_push($mobile, $mobile_chart);
            array_push($graphArray, $graphData);
            array_push($graphavg, $graphavgs);
        }

        // total earns in year
        if (!$request->date) {
            $thisMonth_today = Carbon::now();
        } else {
            $thisMonth_today = Carbon::parse($request->date);
        }
        $year_start = date_format($thisMonth_today, 'Y-01-01');
        $formatted_date = date_create($year_start)->modify('+12 month');
        $year_end = date_format($formatted_date, 'Y-01-01');

        if ($thisMonth_today) {
            $revenue = 0;
            $revenueyearly = 0;
            $bookingThisMonth = 0;
            $monthCreated = 0;
            $bookedDays = 0;
            $bookedDays2 = 0;
            $Occupancy = 0;
            $books = \App\Booking::where('listing_id', $request->listing_id)->where([['status', '>=', 5], ['check_out', '>', $year_start], ['check_in', '<=', $year_end]])->orderBy('check_in', 'ASC')->get();
            foreach ($books as $book) {
                $checkIn = $book->check_in;
                $checkOut = $book->check_out;
                $nights = $book->nights;
                if ($book->check_in >= $thisMonth && $book->check_in <= $endOfMonth) {
                    if ($book->check_out <= $endOfMonth) {
                        $nights = $book->nights;
                    } else {
                        $earlier = new DateTime($endOfMonth);
                        $later = new DateTime($book->check_out);
                        $diff = $later->diff($earlier)->format("%a");
                        $nights = $book->nights - $diff;
                        $checkOut = $endOfMonth;
                    }
                } elseif ($book->check_out > $thisMonth && $book->check_out <= $endOfMonth) {
                    $earlier = new DateTime($book->check_in);
                    $later = new DateTime($thisMonth);
                    $diff = $later->diff($earlier)->format("%a");
                    $nights = $book->nights - $diff;
                    $checkIn = $thisMonth;
                } elseif ($book->check_in <= $thisMonth && $book->check_out >= $endOfMonth) {
                    $nights = date_format($selDate, 't');
                    $checkIn = $thisMonth;
                    $checkOut = $endOfMonth;
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

                if ($checkIn != $checkOut && $checkOut <= $endOfMonth) {
                    //        echo round($nights*$book->price_night, 2).'<br />';
                    $revenue = $revenue + round($nights * $book->price_night, 2);
                    $bookingThisMonth++;
                    $bookedDays = $bookedDays + $nights;
                }
            }
            if ($listing->type == 'group') {
                $bookingThisMonth = 0;
                $nights = 0;
                $bookedDays = 0;
                $revenue = 0;
                $group = \App\ListingGroup::where('listing_id', $id)->first();
                if (!empty($group)) {
                    $listingIds = \App\ListingGroup::where('group_id', $group->group_id)->pluck('listing_id')->toArray();
                    $revenues = \App\Booking::whereIn('listing_id', $listingIds)->where([['status', '>=', 5], ['check_out', '>', $year_start], ['check_in', '<=', $year_end]])->get();
                    foreach ($revenues as $book) {
                        $checkIn = $book->check_in;
                        $checkOut = $book->check_out;
                        $nights = $book->nights;
                        if ($book->check_in >= $thisMonth && $book->check_in <= $endOfMonth) {
                            if ($book->check_out <= $endOfMonth) {
                                $nights = $book->nights;
                            } else {
                                $earlier = new DateTime($endOfMonth);
                                $later = new DateTime($book->check_out);
                                $diff = $later->diff($earlier)->format("%a");
                                $nights = $book->nights - $diff;
                                $checkOut = $endOfMonth;
                            }
                        } elseif ($book->check_out > $thisMonth && $book->check_out <= $endOfMonth) {
                            $earlier = new DateTime($book->check_in);
                            $later = new DateTime($thisMonth);
                            $diff = $later->diff($earlier)->format("%a");
                            $nights = $book->nights - $diff;
                            $checkIn = $thisMonth;
                        } elseif ($book->check_in <= $thisMonth && $book->check_out >= $endOfMonth) {
                            $nights = date_format($selDate, 't');
                            $checkIn = $thisMonth;
                            $checkOut = $endOfMonth;
                        }

                        if ($checkIn != $checkOut && $checkOut <= $endOfMonth) {
                            $revenue = $revenue + ($nights * $book->price_night) + $book->cleaning_fee;
                        }
                    }
                }
            }

            $revenueyearly = $revenue;
        }

        return view('owner.home.index', compact('listing', 'id', 'allListings', 'selDate', 'revenueyearly', 'graphArray', 'graphavg', 'mobile', 'year_start'));
    }

    public function change_password()
    {
        $user_detail = User::where('id', Auth::user()->id)->first();
        return view('owner.home.profile', compact('user_detail'));
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
        // if(Auth::user()->id != 3){
        //     return redirect('/owner/dashboard');
        // }
        $data = $request->only("password");
        $validator = Validator::make($request->all(), [
            'password' => 'nullable|string|max:100',
            'c_password' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $user = User::find($id);
        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }
        if ($request->password == $request->c_password) {

            $user->update($data);
            if ($user) {
                return back()->with('success', 'Password has been changed successfully!', $user);
            }
        } else {
            return back()->with('error', 'Both password & confirm password must be same!', $user);
        }
    }
}

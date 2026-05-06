<?php

namespace App\Http\Controllers\Admin;

use App\Booking;
use App\Listing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $listings = Listing::all();
        return view('admin.home.index', compact('listings'));
    }

    public function view_calendar(Request $request)
    {
        $date = $request->date;
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $start = date_create($date);
        $end = date_create($date)->modify('+6 days');
        $groups = Booking::where([['check_out', '>=', $start], ['check_in', '<=', $end]])->get()->groupBy('listing_id');
        $newDate = date_create($date);
        $dateArray[] = date_format($newDate, 'Y-m-d');
        for ($i = 0; $i < 6; $i++) {
            $newDate->modify('+1 day');
            $dateArray[] = date_format($newDate, 'Y-m-d');
        }

        $bookedListingId = [];
        foreach ($groups as $group) {
            foreach ($dateArray as $date22) {
                $booked = false;
                foreach ($group as $book) {
                    if ($date22 >= $book->check_in && $date22 < $book->check_out) {
                        $booked = true;
                        break;
                    }
                }
                if ($booked === false) {
                    break;
                }
                //                echo $date22.$booked.'<br />';
            }
            if ($booked === true) {
                $bookedListingId[] = $group[0]->listing_id;
                //                    break;
            }
        }
        $listings = Listing::all();
        return view('admin.home.viewCalendar', compact('listings', 'start', 'end', 'dateArray', 'date'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function subscribeList()
    {
        $subs = DB::table('subscribe')->get();
        return view('admin.home.subscribeList', compact('subs'));
    }

    /**
     * file manager
     */
    public function filemanager()
    {
        return view('admin.home.filemanager');
    }
}

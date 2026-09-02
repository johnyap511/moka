<?php

namespace App\Http\Controllers\Owner;

use App\Listing;
use App\ListingReport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function revenue(Request $request){
        $authId = Auth::user()->id;
        $listingId = $request->listing_id;
        $year = $request->year;
        if(empty($listingId)){
            $listingIds = Listing::withArchived()->where('user_id', $authId)->pluck('id')->toArray();
        }else{
            $listingIds[] = $listingId;
        }

        if(empty($year)){
            $revenues = ListingReport::whereIn('listing_id',$listingIds)->get();
        }else{
            $revenues = ListingReport::whereIn('listing_id',$listingIds)->where('year', $year)->get();
        }
        return view('owner.report.revenue',compact('revenues','listingId', 'year'));
    }



    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function payment(Request $request){
        $authId = Auth::user()->id;
        $listingId = $request->listing_id;
        $year = $request->year;
        if(empty($listingId)){
            $listingIds = Listing::withArchived()->where('user_id', $authId)->pluck('id')->toArray();
        }else{
            $listingIds[] = $listingId;
        }

        if(empty($year)){
            $revenues = ListingReport::whereIn('listing_id',$listingIds)->get();
        }else{
            $revenues = ListingReport::whereIn('listing_id',$listingIds)->where('year', $year)->get();
        }

        return view('owner.report.payment',compact('revenues','listingId', 'year'));
    }


}

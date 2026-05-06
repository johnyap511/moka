<?php

namespace App\Http\Controllers\User;

use App\Booking;
use App\Listing;
use App\ListingCategory;
use App\ListingPrice;
use App\OtherModel\ListingZone;
use App\OtherModel\Zone;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ListingController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search(Request $request){
        $selectedZone = Zone::where('name', $request->location)->first();
        if(empty($selectedZone)){
            return back()->with('error', 'Sorry there is no property available in the '.$request->location.'!');
        }


        $zones = Zone::where('status', 1)->pluck('name')->toArray();
        $listingId = ListingZone::where('zone_id', $selectedZone->id)->pluck('listing_id')->toArray();
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $guestNo = $request->guest;
        $children = $request->children;
        if(!empty($checkIn) && $checkIn >= $checkOut){
            return back()->with('error', 'The check in date should be earlier than check out date!');
        }
        if(!empty($checkIn) && !empty($checkOut)){
            $bookedListingIds = Booking::where([['check_in', '>=', $checkIn],['check_in', '<', $checkOut],['status', '>=', 3]])
                ->orWhere([['check_in', '<=', $checkIn],['check_out', '>', $checkIn],['status', '>=', 3]])->pluck('listing_id')->toArray();
            $listingId=array_diff($listingId,$bookedListingIds);
        }

        $listings = Listing::whereIn('id', $listingId)->where('status', 1)->get();

        Session::put('check_in', $checkIn);
        Session::put('check_out', $checkOut);
        Session::put('guest', $guestNo);
        Session::put('children', $children);
        return view('auth.newTheme.propertySearch', compact('listings', 'zones','selectedZone', 'checkOut', 'checkIn', 'guestNo','children'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function popularListing(){
        $listingIds = Booking::where('status', '>=', 5)->select('listing_id')->get()->groupBy('listing_id')->toArray();
        array_multisort(array_map('count', $listingIds), SORT_DESC, $listingIds);
        $listingIds = array_slice($listingIds, 0, 30);
        $ids = [];
        foreach ($listingIds as $listingId){
            $ids[] = $listingId[0]['listing_id'];
        }
        $listings = Listing::where('status', 1)->whereIn('id',$ids)->take(15)->get();
        return view('auth.newTheme.popularListing', compact('listings'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function propertiesType($type){
        if($type === 'short'){
            $catId = 1;
        }elseif($type === 'medium'){
            $catId = 2;
        }else{
            $catId = 3;
        }
        $listingIds = ListingCategory::where('category_id', $catId)->pluck('listing_id')->toArray();
        $listings = Listing::where('status', 1)->whereIn('id',$listingIds)->get();
        return view('auth.newTheme.propertiesType', compact('listings', 'type'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function listingDetail($listingId){
        if(is_numeric($listingId)){
            $listing = Listing::find($listingId);
        }else{
            $listing = Listing::where('key', $listingId)->first();
        }

        if(empty($listing)){
            return back()->with('error', 'Property not found!');
        }
        $checkIn = Session::get('check_in');
        $checkOut = Session::get('check_out');
        $guestNo = Session::get('guest');
        $children = Session::get('children');
        if(empty($guestNo)){
            $guestNo = 0;
        }
        if(empty($children)){
            $children = 0;
        }


//        $today = date("Y-m-d");
        if(!empty($checkIn) && !empty($checkOut)){
            $booked = Booking::where([['listing_id',$listingId],['check_in', '<=', $checkIn],['check_out', '>', $checkIn],['status', '>=', 3]])
                ->orWhere([['listing_id',$listingId],['check_in', '<=', $checkOut],['check_out', '>', $checkOut],['status', '>=', 3]])
                ->orWhere([['listing_id',$listingId],['check_in', '>=', $checkIn],['check_out', '<=', $checkOut],['status', '>=', 3]])->first();
        }

        if(!empty($booked)){
            return back()->with('error', 'Sorry this property is booked in your selected dates, please choose other dates or select another property!');
        }
//        $books = Booking::where([['listing_id', $listingId], ['status', 5], ['check_out', '>=', $today]])->get();
//        foreach($books as $book){
//            if($book->check_in<$checkOut && $book->check_out>$checkIn){
//                return back()->with('error', 'These dates are not available!');
//            }
//        }
        return view('auth.newTheme.propertyDetail', compact('listing', 'checkIn', 'checkOut', 'guestNo','children'));
    }




    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function preBooking(Request $request){
        $data = $request->only("check_in","check_out","adult","infant","listing_id", "remark");
        $validator = Validator::make($request->all(), [
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adult' => 'required|numeric',
            'infant' => 'nullable|numeric',
            'listing_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if(!isset($data['infant'])){
            $data['infant'] = 0;
        }

        if($data['check_out']<=$data['check_in']){
            return back()->with('error', 'The check out should be bigger than check in!');
        }


        $today = date("Y-m-d");
        $books = Booking::where([['listing_id', $request->listing_id], ['status', 5], ['check_out', '>=', $today]])->get();
        foreach($books as $book){
            if($book->check_in<$data['check_out'] && $book->check_out>$data['check_in']){
                return back()->with('error', 'These dates are not available!');
            }
        }

        $listing = Listing::where('id', $data['listing_id'])->first();
        $data['user_id'] = Auth::user()->id;
        $bookingStartDate = date_create($data['check_in']);
        $bookingEndDate = date_create($data['check_out']);
        $interval = date_diff($bookingStartDate, $bookingEndDate);
        $data['nights'] = $interval->format('%a');

        $data['price'] = 0;
        while($bookingStartDate <= $bookingEndDate){
            $price = ListingPrice::where([['listing_id', $data['listing_id']], ['date', date_format($bookingStartDate, "Y-m-d")]])->first();
            if(empty($price)){
                $price = $listing->default_price;
            }else{
                $price = $price->price;
            }
            $data['price'] = $data['price']+ $price;
            $bookingStartDate->modify('+1 day');
        }
//        $data['status'] = 1;

        $data['status'] = 3;

        $booking = Booking::create($data);
        return redirect('/user/booking/history')->with('success', 'You are booked the listing successfully!');
        return view('user.home.bookingDetail', compact('booking'));
    }


}

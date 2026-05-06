<?php

namespace App\Http\Controllers\User;

use App\Booking;
use App\Listing;
use App\ListingCategory;
use App\ListingPrice;
use App\ListingPriceDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function checkAvailable(Request $request, $listingId){
        $validator = Validator::make($request->all(), [
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adult' => 'required|numeric',
            'children' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if(Auth::check() == false){
            return back()->with('error', 'Please register or login to continue!');
        }

        if(is_numeric($listingId)){
            $listing = Listing::find($listingId);
        }else{
            $listing = Listing::where('key', $listingId)->first();
        }
        $listingId = $listing->id;
//        dd($listingId);
        if(empty($listing)){
            return back()->with('error', 'Listing not found!');
        }
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $guest = $request->adult;
        $children = $request->children;
        Session::put('check_in', $checkIn);
        Session::put('check_out', $checkOut);
        Session::put('guest', $guest);
        Session::put('children', $children);
        if($guest<1){
            return back()->with('error', 'Please select number of guest before proceed!');
        }
//        $booked = Booking::where([['listing_id',$listingId],['check_in', '<=', $checkIn],['check_out', '>', $checkIn],['status', '>=', 3]])->first();
//        if(!empty($booked)){
//            dd(1,$booked);
//        }
//        $booked = Booking::where([['listing_id',$listingId],['check_in', '<=', $checkOut],['check_out', '>', $checkOut],['status', '>=', 3]])->first();
//        if(!empty($booked)){
//            dd(2,$booked);
//        }
//        $booked = Booking::where([['listing_id',$listingId],['check_in', '>=', $checkIn],['check_out', '<=', $checkOut],['status', '>=', 3]])->first();
//        dd(3,$booked);

        $booked = Booking::where([['listing_id',$listingId],['check_in', '<=', $checkIn],['check_out', '>', $checkIn],['status', '>=', 3]])
            ->orWhere([['listing_id',$listingId],['check_in', '<=', $checkOut],['check_out', '>', $checkOut],['status', '>=', 3]])
            ->orWhere([['listing_id',$listingId],['check_in', '>=', $checkIn],['check_out', '<=', $checkOut],['status', '>=', 3]])->first();
        if(!empty($booked)){
            return back()->with('error', 'Sorry this property is booked in your selected dates, please choose other dates or select another property!');
        }
        if($checkIn == $checkOut){
            return back()->with('error', 'The check in and check out can not be in same date!');
        }
        if($checkIn < date('Y-m-d')){
            return back()->with('error', 'The check in date can not be earlier than today!');
        }
        $checkInDate = date_create($checkIn);
        $checkOutDate = date_create($checkOut);
        if($checkOutDate < $checkInDate){
            return back()->with('error', 'The check out can not be earlier than check in!');
        }

        $listingCategory = ListingCategory::where('listing_id', $listing->id)->first();
        $categoryId = $listingCategory->category_id ?? null;
        if(!empty($categoryId)){
            $months = date_diff($checkOutDate, $checkInDate)->format('%m');
            $year = date_diff($checkOutDate, $checkInDate)->format('%y');
            $months = ($year*12)+$months;
            if($categoryId == 2 && $months < 1){
                return back()->with('error', 'You need to book at least one month!');
            }elseif($categoryId == 3 && $months < 6){
                return back()->with('error', 'You need to book at least six months!');
            }
        }

        $nights = date_diff($checkOutDate, $checkInDate)->days;

        $totalPrice = 0;
        $i = 0;
        while($i < $nights){
            $listingPrice = ListingPrice::where([['listing_id', $listingId], ['date', date_format($checkInDate, 'Y-m-d')]])->first();
            if(empty($listingPrice)){
                $totalPrice = $totalPrice+$listing->default_price;
            }else{
                $totalPrice = $totalPrice+$listingPrice->price;
            }
            $checkInDate->modify('+1 day');
            $i++;
        }

        if($listing->tourism_tax_type == 'percentage'){
            $tourismTax = ($listing->tourism_tax_amount*$totalPrice)/100;
        }else{
            $tourismTax = $listing->tourism_tax_amount;
        }
        $listingPD = ListingPriceDetail::where('listing_id', $listing->id)->first();
        if(empty($listingPD)){
            $listingPD = ListingPriceDetail::create(['listing_id'=>$listing->id]);
        }
        return view('auth.newTheme.confirm', compact('listing','checkIn', 'checkOut', 'guest','children', 'nights', 'totalPrice','tourismTax','listingPD','categoryId'));
    }



    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function checkPromo(Request $request){
        $listingPD = ListingPriceDetail::where('listing_id', $request->listing_id)->first();
        if(empty($listingPD) || $listingPD->promo_has !=1 || $listingPD->promo_code != $request->code){
            $data['success'] = false;
            $data['error'] = 'Invalid Promo code!';
            return $data;
        }
        $data['success'] = true;
        $data['amount'] = $listingPD->promo_amount;
        return $data;
    }



}

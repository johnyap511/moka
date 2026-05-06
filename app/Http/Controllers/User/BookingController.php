<?php

namespace App\Http\Controllers\User;

use App\Booking;
use App\RateReview;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function reviewStore(Request $request){
        $validator = Validator::make($request->all(), [
            'rate' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $bookId = $request->book_id;
        $authId = Auth::user()->id;
        $book = Booking::find($bookId);
        if(empty($book) || $book->user_id != $authId){
            return back()->with('error', 'You do not have permission to review this listing!');
        }
        $exist = RateReview::where('booking_id', $bookId)->first();
        if(!empty($exist)){
            return back()->with('error', 'You reviewed this booking already!');
        }

        RateReview::create(['listing_id'=>$book->listing_id,'user_id'=>$authId,'booking_id'=>$bookId,'listing_rate'=>$request->rate,'listing_review'=>$request->review]);
        return back()->with('success', 'You reviewed this booking successfully!');
    }

}

<?php

namespace App\Http\Controllers\Admin;

use App\RateReview;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApprovalReviewController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(){
        $reviews = RateReview::all();
        return view('admin.approval.reviewList',compact('reviews'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id){
        $review = RateReview::find($id);
        return view('admin.approval.reviewEdit',compact('review'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update(Request $request, $id){
        $review = RateReview::find($id);
        $data = $request->only('listing_rate','listing_review','status');
        $review->update($data);
        return back()->with('success', 'Review updated successfully!');
    }

}

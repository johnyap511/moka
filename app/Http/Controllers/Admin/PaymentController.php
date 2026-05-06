<?php

namespace App\Http\Controllers\Admin;

use App\Listing;
use App\ListingReport;
use App\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function upcoming(Request $request)
    {
        $year = $request->year;
        $month = $request->month;
        $listingId = $request->listing_id;
        $status = $request->status;

        if(!empty($status) && !empty($listingId)){
            $payIds = Payment::whereNotNull('receipt')->where([['listing_id', $listingId],['status', $status]])->pluck('report_id')->toArray();
        }elseif(!empty($listingId)){
            $payIds = Payment::whereNotNull('receipt')->where('listing_id', $listingId)->pluck('report_id')->toArray();
        }elseif(!empty($status)){
            $payIds = Payment::whereNotNull('receipt')->where('status', '!=', $status)->pluck('report_id')->toArray();
        }else{
            $payIds = Payment::whereNotNull('receipt')->pluck('report_id')->toArray();
        }

        if(!empty($year) && !empty($month)){
            $reports = ListingReport::whereNotIn('id', $payIds)->where([['year', $year],['month', $month]])->get();
        }elseif(!empty($year)){
            $reports = ListingReport::whereNotIn('id', $payIds)->where('year', $year)->get();
        }elseif(!empty($month)){
            $reports = ListingReport::whereNotIn('id', $payIds)->where('month', $month)->get();
        }elseif($request->filter == 'true'){
            $reports = ListingReport::whereNotIn('id', $payIds)->get();
        }else{
            $reports = ListingReport::whereNotIn('id', $payIds)->where([['year', date('Y')],['month', date('m')]])->get();
        }

        return view('admin.payment.upcoming', compact('reports', 'year', 'month', 'status', 'listingId'));
    }


    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function paymentUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $report = ListingReport::find($id);
        if(empty($report)){
            return back()->with('error', 'Invalid listing report!');
        }

        $listing = Listing::find($report->listing_id);
        if(empty($listing)){
            return back()->with('error', 'Invalid listing!');
        }

        $payment = Payment::where('report_id', $id)->first();

        $paymentData['status'] = $request->status;
        $file = $request->receipt;
        if(!empty($file)){
            $paymentData['receipt'] = Storage::disk('upload_files')->put('/receipt', $file);
        }

        if(empty($payment)){
            $paymentData['user_id'] = $listing->user_id;
            $paymentData['report_id'] = $id;
            $paymentData['listing_id'] = $report->listing_id;
            $paymentData['amount'] = $report->owner_income;
            Payment::create($paymentData);
        }else{
            $payment->update($paymentData);
        }
        return back()->with('success', 'Payment updated successfully!');
    }


    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function priceUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'owner_income' => 'required|numeric',
            'platform_income' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $report = ListingReport::find($id);
        if(empty($report)){
            return back()->with('error', 'Invalid listing report!');
        }

        $listing = Listing::find($report->listing_id);
        if(empty($listing)){
            return back()->with('error', 'Invalid listing!');
        }

        $dataR = $request->only('owner_income', 'platform_income');
        $report->update($dataR);
        return back()->with('success', 'Price updated successfully!');
    }


    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function past()
    {
        $payIds = Payment::whereNotNull('receipt')->pluck('report_id')->toArray();
        $reports = ListingReport::whereIn('id', $payIds)->get();
        return view('admin.payment.past', compact('reports'));
    }

}

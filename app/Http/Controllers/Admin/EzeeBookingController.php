<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\OtherModel\EzeeBooking;
use Illuminate\Http\Request;

class EzeeBookingController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $booking = EzeeBooking::findOrFail($id);
        return view('admin.listing.book.ezeeBookEdit', compact('booking'));
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
        $booking = EzeeBooking::findOrFail($id);
        $validatedData = $request->validate([
            'FirstName' => 'required|string|max:255', 
            'LastName' => 'required|string|max:255',
            'Email' => 'nullable|email|max:255',
            'Mobile' => 'nullable|string|max:20',
            'Start' => 'required|date',
            'End' => 'required|date|after:Start',
            'Source' => 'required|string|max:50',
            'TotalAmountBeforeTax' => 'required|numeric',
            'TotalExtraCharge' => 'nullable|numeric',
            'TotalDiscount' => 'nullable|numeric',
            // Correctable without touching how the fee breakdown is derived:
            // EzeePricing reads TotalAmountBeforeTax, TotalExtraCharge and
            // TotalDiscount, none of which are added here. RoomName is the EZEE
            // unit id the auto-assignment matches on, so being able to fix a
            // wrong one by hand matters.
            'folio_no' => 'nullable|string|max:50',
            'RoomName' => 'nullable|string|max:255',
            'Country' => 'nullable|string|max:100',
            'TotalAmountAfterTax' => 'nullable|numeric',
        ]);

        $booking->update($validatedData);

        // Get the previous URL to determine where to redirect
        $redirectPath = '/admin/ezee/unassigned_booking';
        
        // Check if the booking is assigned to a listing
        if ($booking->status == '8') {
            $redirectPath = '/admin/ezee/assigned_booking';
        }
        
        return redirect($redirectPath)
            ->with('success', 'Booking updated successfully');
    }
}
<?php

namespace App\Listeners;

use App\Booking;
use App\DataLog;
use App\Listing;
use App\Mail\AdminBookNotifyMail;
use App\Mail\BookingConfirmationGuest;
use App\OtherModel\BookingDetail;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class BookingCompleteListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $bookEventData = $event->bookEventData;
        $dataLog = DataLog::create([
            'related_id'=>$bookEventData['book_id'],
            'title'=>'sendConfirmationEmail',
            'data'=>json_encode($bookEventData), 
            'status'=>'started']);
        $book = Booking::find($bookEventData['book_id']);
        if(empty($book)){
            return;
        }
        $listing = Listing::find($book->listing_id);
        $user = User::find($book->user_id);
        $checkIn = date_create($book->check_in);
        $checkOut = date_create($book->check_out);

        $data22['banner'] = $listing->banner ?? '';
        if(!empty($data22['banner'])){
            $data22['banner'] = asset('/images/listing/'.$data22['banner']);
        }
        $data22['listing_title'] = $listing->title ?? '';
        $data22['listing_address'] = $listing->address ?? '';
        $data22['book_id'] = $book->id;
        $data22['user_name'] = $user->name ?? ''.' '.$user->last_name ?? '';
        $data22['user_phone'] = $user->phone ?? '';
        $data22['check_in'] = date_format($checkIn,'l, j F Y').' (after 15:00)';
        $data22['check_out'] = date_format($checkOut,'l, j F Y').' (before 12:00)';
        $data22['nights'] = $book->nights;
        $data22['pax'] = $book->adult.' adults, '.$book->infant.' children';
        $data22['special_request'] = $book->remark;
        $data22['price_night'] = $book->price_night;
        $data22['cleaning_fee'] = $book->cleaning_fee;
        $data22['tourism_tax'] = $book->tourism_tax;

        $data22['total_price'] = $book->price;
        $data22['user_first_name'] = $user->name ?? '';

        $det = BookingDetail::where('booking_id', $book->id)->first();
        $data22['insurance'] = $det->insurance ?? 0;
        $data22['promo'] = $det->promo ?? 0;

        $userEmail = $user->email ?? '';
        $dataLog->update(['status'=>'sending']);
        Mail::to('rsvp@homemoka.com')->queue(new AdminBookNotifyMail($data22));
        $dataLog->update(['status'=>'admin send 1']);
        Mail::to('sam@homemoka.com')->queue(new AdminBookNotifyMail($data22));
        $dataLog->update(['status'=>'admin send 2']);
        if(!empty($userEmail)){
            Mail::to($userEmail)->queue(new BookingConfirmationGuest($data22));
            $dataLog->update(['status'=>'Completed']);
        }
        $dataLog->update(['status'=>'No user email']);

    }
}

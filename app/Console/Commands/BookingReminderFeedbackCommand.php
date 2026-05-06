<?php

namespace App\Console\Commands;

use App\Booking;
use App\DataLog;
use App\Listing;
use App\Mail\BookFeedbackRequestMail;
use App\Mail\BookReminderMail;
use App\OtherModel\UserMail;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class BookingReminderFeedbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send booking reminder and booking feedback emails';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tomorrow = Carbon::now()->modify('+1 day');
        $bookings = Booking::where([['check_in', date_format($tomorrow, 'Y-m-d')],['status', '>=', 3]])->get();
        foreach ($bookings as $book){
            $sentBefore = UserMail::where([['user_id', $book->user_id], ['mailable_type', 'book_reminder'], ['mailable_id', $book->id]])->first();
            if(empty($sentBefore)){
                $dataLog = DataLog::create(['related_id'=>$book->id,'title'=>'sendBookReminderEmail','data'=>'', 'status'=>'started']);
                $listing = Listing::find($book->listing_id);
                $user = User::find($book->user_id);
                $checkIn = date_create($book->check_in);
                $checkOut = date_create($book->check_out);
                $fName = $user->name ?? '';
                $lName = $user->last_name ?? '';
                $phone = $user->phone ?? '';

                $data22['listing_title'] = $listing->title ?? '';
                $data22['book_id'] = $book->id;
                $data22['user_name'] = $fName.' '.$lName;
                $data22['user_phone'] = $phone;
                $data22['check_in'] = date_format($checkIn,'l, j F Y').' (after 15:00)';
                $data22['check_out'] = date_format($checkOut,'l, j F Y').' (before 12:00)';
                $data22['nights'] = $book->nights;
                $data22['pax'] = $book->adult.' adults, '.$book->infant.' children';
                $data22['special_request'] = $book->remark;
                $data22['user_first_name'] = $fName;
                $userEmail = $user->email ?? '';
                $dataLog->update(['status'=>'passed data']);
                if(!empty($book->user_id)){
                    $dataLog->update(['status'=>'user ID not empty']);
                    $userMail = UserMail::create(['user_id'=>$book->user_id, 'mailable_type'=>'book_reminder', 'mailable_id'=>$book->id, 'status'=>1]);
                }
                if(!empty($userEmail)){
                    $dataLog->update(['status'=>'user mail not empty']);
                    Mail::to($userEmail)->queue(new BookReminderMail($data22));
                }

                if(!empty($userMail)){
                    $userMail->update(['status'=>2]);
                }
            }
        }


        if(date('H') >= 13 && date('H')<= 15){
            $bookings = Booking::where([['check_out', date('Y-m-d')],['status', '>=', 3]])->get();
            foreach ($bookings as $book){
                $sentBefore = UserMail::where([['user_id', $book->user_id], ['mailable_type', 'book_feedback'], ['mailable_id', $book->id]])->first();
                if(empty($sentBefore)){
                    $dataLog = DataLog::create(['related_id'=>$book->id,'title'=>'sendBookFeedbackEmail','data'=>'', 'status'=>'started']);
                    $user = User::find($book->user_id);
                    if(!empty($book->user_id)) {
                        $userMail = UserMail::create(['user_id' => $book->user_id, 'mailable_type' => 'book_feedback', 'mailable_id' => $book->id, 'status' => 1]);
                    }
                    $data22['user_first_name'] = $user->name ?? '';
                    $userEmail = $user->email ?? '';
                    $books = Booking::select('listing_id', \DB::raw('count(*) as total'))
                        ->groupBy('listing_id')->orderBy('total', 'desc')->take(3)->get();
                    $i=0;
                    foreach ($books as $book2){
                        $list = Listing::find($book2->listing_id);
                        $data22['l_img_'.$i] = $list->banner;
                        $data22['l_name_'.$i] = $list->title;
                        $data22['l_address_'.$i] = $list->address;
                        $data22['l_price_'.$i] = $list->default_price;
                    }
                    $dataLog->update(['status'=>'passed data']);
                    if(!empty($userEmail)){
                        $dataLog->update(['status'=>'user mail not empty']);
                        Mail::to($userEmail)->queue(new BookFeedbackRequestMail($data22));
                    }
                    if(!empty($userMail)){
                        $userMail->update(['status'=>2]);
                    }
                }
            }
        }


    }
}

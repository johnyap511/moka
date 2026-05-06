<?php

namespace App\Listeners;

use App\Booking;
use App\BookingApi;
use App\DataLog;
use App\EzeeGroup;
use App\EzeeGroupListing;
use App\Listing;
use App\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookingCompleteEZEEAPIListener
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
        $dataLog = DataLog::create(['related_id'=>$bookEventData['book_id'],'title'=>'sendezeebookings','data'=>json_encode($bookEventData), 'status'=>'started']);
        $bookAPI = BookingApi::where('book_id',$bookEventData['book_id'])->first();
        $book = Booking::find($bookEventData['book_id']);
        if(empty($book)){
            $dataLog->update(['status'=>'Can not find booking']);
            return;
        }
        $listing = Listing::find($book->listing_id);
        if(empty($listing)){
            $dataLog->update(['status'=>'Can not find listing']);
            return;
        }elseif(empty($listing->room_type)){
            $dataLog->update(['status'=>'Can not find room type']);
            return;
        }
        $dataLog->update(['status'=>'passed listing']);

        $ezeeGroup = EzeeGroupListing::where('listing_id', $listing->id)->first();
        if(empty($ezeeGroup)){
            $dataLog->update(['status'=>'No EZEE Group']);
            return;
        }

        $ezeeGroup = EzeeGroup::where('id', $ezeeGroup->ezee_group_id)->first();
        if(empty($ezeeGroup)){
            $dataLog->update(['status'=>'No EZEE Group']);
            return;
        }

        if(empty($bookAPI)){
            $checkIn=$book->check_in;
            $checkOut=$book->check_out;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://live.ipms247.com/booking/reservation_api/listing.php?request_type=RoomList&HotelCode=" . $ezeeGroup->hotel_code . "&APIKey=" . $ezeeGroup->auth_key . "&check_in_date=" . $checkIn . "&check_out_date=" . $checkOut);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($server_output, true);
            $correct = false;
            foreach ($res as $result) {
                if (key_exists('Error Details', $result)) {
                    $dataLog->update(['status'=>$result['Error Details']['Error_Message']]);
                    return;
                }
                if ($result['Room_Name'] == $listing->room_type) {
                    $avas = $result['available_rooms'];
                    foreach ($avas as $ava) {
                        if ($ava < 1) {
                            $dataLog->update(['status'=>'The room is not available in selected dates!']);
                            return;
                        }
                    }
                    $correct = true;
                    $Rateplan_Id = $result['roomrateunkid'];
                    $Ratetype_Id = $result['ratetypeunkid'];
                    $Roomtype_Id = $result['roomtypeunkid'];
                }
            }
            if ($correct === false) {
                $dataLog->update(['status'=>'The room type is invalid!']);
                return;
            }
            $bookAPI = BookingApi::create(['book_id' => $book->id, 'listing_id' => $listing->id, 'Rateplan_Id' => $Rateplan_Id, 'Ratetype_Id' => $Ratetype_Id, 'Roomtype_Id' => $Roomtype_Id, 'last_try' => Carbon::now()]);
        }

        $baserate='';
        $extradultrate='';
        $extrachildrate='';
        $bookingStartDate = date_create($book->check_in);
        $bookingEndDate = date_create($book->check_out);
        $interval = date_diff($bookingStartDate, $bookingEndDate);
        $nights = $interval->format('%a');
        for($i=0;$i<$nights;$i++){
            $baserate=$baserate.$book->price.',';
            $extradultrate=$extradultrate.'0,';
            $extrachildrate=$extrachildrate.'0,';
        }
        $baserate=substr($baserate,0,-1);
        $extradultrate=substr($extradultrate,0,-1);
        $extrachildrate=substr($extrachildrate,0,-1);

        sleep(1);
        $user = User::find($book->user_id);
        $email = $user->email ?? '';
        $firstName = $user->name ?? '';
        $lastName = $user->last_name ?? '';
        $postData22 = '{"Room_Details":{"Room_1":{"Rateplan_Id":"' . $bookAPI->Rateplan_Id . '","Ratetype_Id":"' . $bookAPI->Ratetype_Id . '","Roomtype_Id":"' . $bookAPI->Roomtype_Id . '","baserate":"' .$baserate. '","extradultrate":"'.$extradultrate.'","extrachildrate":"'.$extrachildrate.'","number_adults":"' . $book->adult . '","number_children":"0","ExtraChild_Age":"0","Title":"","First_Name":"' . $firstName . '","Last_Name":"' . $lastName . '"}},"check_in_date":"' . $book->check_in . '","check_out_date":"' . $book->check_out . '","Booking_Payment_Mode":"","Email_Address":"' . $email . '"}';
        $url = 'https://live.ipms247.com/booking/reservation_api/listing.php?request_type=InsertBooking&HotelCode=' . $ezeeGroup->hotel_code . "&APIKey=" . $ezeeGroup->auth_key . '&BookingData='.urlencode($postData22);
        $dataLog->update(['data'=>$url,'status'=>'passed room type']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        $dataLog->update(['data'=>$url.' ||| result: '.$server_output,'status'=>'success']);
        $res = json_decode($server_output, true);
        if (is_array($res) && key_exists('ReservationNo', $res)) {
            $bookAPI->update([
                'success_call' => 1, 
                'ReservationNo'=>$res['ReservationNo']
            ]);
        }else{
            $bookAPI->update(['success_call' => 0, 'error' => json_encode($res)]);
            $dataLog->update(['status'=>'Failed']);
        }

    }
}

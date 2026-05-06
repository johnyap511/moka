<?php

namespace App\Listeners;

use App\Booking;
use App\BookingApi;
use App\DataLog;
use App\EzeeGroup;
use App\EzeeGroupListing;
use App\Listing;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookingCompleteEZEEPaymentListener
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
        $dataLog = DataLog::create(['related_id'=>$bookEventData['book_id'],'title'=>'sendezeepayment','data'=>json_encode($bookEventData), 'status'=>'started']);
        $book = Booking::find($bookEventData['book_id']);
        $bookAPI = BookingApi::where('book_id',$bookEventData['book_id'])->first();
        if(empty($book)){
            $dataLog->update(['status'=>'Can not find booking']);
            return;
        }
        if(empty($bookAPI)){
            $dataLog->update(['status'=>'Can not find booking API']);
            return;
        }
        if(empty($bookAPI->ReservationNo)){
            $dataLog->update(['status'=>'No ReservationNo']);
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
        $ezeeGroup = EzeeGroup::where('id', $ezeeGroup->ezee_group_id)->first();
        if(empty($ezeeGroup)){
            $dataLog->update(['status'=>'No EZEE Group']);
            return;
        }

        $paymentID = null;
        $currencyID = null;
        $paymentType = 'Bank';
        if($book->status == 4){
            $paymentType = 'Cash';
        }
        $postData['Request_Type'] = 'RetrievePayMethods';
        $postData['Authentication'] = [
            'HotelCode'=>$ezeeGroup->hotel_code,
            'AuthCode'=>$ezeeGroup->auth_key
        ];
        $pd['RES_Request'] = $postData;
        $payload = json_encode($pd);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://live.ipms247.com/index.php/page/service.kioskconnectivity");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close ($ch);
        $res = json_decode($server_output, true);
        if(key_exists('Success',$res)){
            if(key_exists('PayMethods',$res['Success'])){
                $methods = $res['Success']['PayMethods'];
                foreach($methods as $method){
                    if($method['Type'] == $paymentType){
                        $paymentID = $method['PaymentID'];
                    }
                }
            }
        }


        $postData = null;
        $postData['Request_Type'] = 'RetrieveCurrency';
        $postData['Authentication'] = ['HotelCode'=>$ezeeGroup->hotel_code,'AuthCode'=>$ezeeGroup->auth_key];
        $pd['RES_Request'] = $postData;
        $payload = json_encode($pd);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://live.ipms247.com/index.php/page/service.kioskconnectivity");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close ($ch);
        $res = json_decode($server_output, true);
        if(key_exists('Success',$res)){
            if(key_exists('CurrencyList',$res['Success'])){
                $curs = $res['Success']['CurrencyList'];
                foreach($curs as $cur){
                    if($cur['CurrencyCode'] == 'MYR'){
                        $currencyID = $cur['CurrencyID'];
                    }
                }
            }
        }

        if(empty($currencyID) || empty($paymentID)){
            $dataLog->update(['status'=>'Currency or payment id not found!']);
            return;
        }

        $postData = null;
        $postData['Request_Type'] = 'AddPayment';
        $postData['Authentication'] = ['HotelCode'=>$ezeeGroup->hotel_code,'AuthCode'=>$ezeeGroup->auth_key];
        $postData['Reservation'] = ['BookingId'=>$bookAPI->ReservationNo,'PaymentId'=>$paymentID,'CurrencyId'=>$currencyID,'Payment'=>$book->price];
        $pd['RES_Request'] = $postData;
        $payload = json_encode($pd);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://live.ipms247.com/index.php/page/service.kioskconnectivity");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close ($ch);
        $res = json_decode($server_output, true);
        $dataLog->update(['data'=>'result: '.$server_output,'status'=>'success']);
        if(key_exists('Success',$res)){
            if(key_exists('Receipt',$res['Success'])){
                $receipt = $res['Success']['Receipt'];
            }else{
                $dataLog->update(['status'=>'Failed']);
            }
        }

    }
}

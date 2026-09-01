<?php

namespace App\Console\Commands;

use App\DataLog;
use App\EzeeGroup;
use App\Listing;
use App\OtherModel\EzeeBooking;
use Illuminate\Console\Command;

class EZEEGetBookingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ezee:bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get EZEE bookings';

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
        ini_set('max_execution_time', 300);
        $listings = EzeeGroup::all();
//        $dataLog = DataLog::create(['title'=>'getezeebookings','data'=>count($listings), 'status'=>'started']);
        $postData['Request_Type'] = 'Bookings';
        foreach ($listings as $listing){
            $postData['Authentication'] = [
                'HotelCode'=>$listing->hotel_code,
                'AuthCode'=>$listing->auth_key
            ];
            $pd['RES_Request'] = $postData;
            $payload = json_encode($pd);
            $notifyList = [];
            $ch = curl_init();
            curl_setopt(
                $ch, CURLOPT_URL,
                "https://live.ipms247.com/pmsinterface/pms_connectivity.php"
            );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array('Content-Type:application/json')
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($server_output, true);
        //    $dataLog->update(['data'=>$server_output, 'status'=>'processed']);
            $dataLog = DataLog::create([
                'title'=>'rawdata',
                'data'=>$server_output,
                'related_id'=>$listing->hotel_code,
                'status'=>'rawdata'
            ]);
            if(is_array($res)){
                foreach($res as $reservation){
                    if(is_array($reservation) && key_exists('Reservation',$reservation)){
                        foreach ($reservation['Reservation'] as $reserve){
                            if(key_exists('BookingTran',$reserve)){
                                foreach($reserve['BookingTran'] as $BookingTran){
                                    $reserveData = $BookingTran;
                                    $exist = EzeeBooking::where([['SubBookingId', $reserveData['SubBookingId']],['TransactionId',$reserveData['TransactionId']]])->first();
                                    if(empty($exist)){
                                        $dataLog = DataLog::create([
                                            'title'=>'sendnotify',
                                            'data'=>'',
                                            'related_id'=>$listing->hotel_code,
                                            'status'=>'started'
                                        ]);
                                        $exist = EzeeBooking::create([
                                            'SubBookingId'=>$reserveData['SubBookingId'],
                                            'TransactionId'=>$reserveData['TransactionId'],
                                            'IsConfirmed'=>$reserveData['IsConfirmed'],
                                            'RateplanName'=>$reserveData['RateplanName'],
                                            'RoomTypeName'=>$reserveData['RoomTypeName'],
                                            // EZEE identifies the unit as eZeePMSRoomid (e.g. "C2-07-10")
                                            // and does not send a RoomName field on bookings. Reading
                                            // RoomName alone wrote null every time, which is how the
                                            // unit was lost on every booking this command imported.
                                            'RoomName'=>$reserveData['eZeePMSRoomid'] ?? ($reserveData['RoomName'] ?? null),
                                            'Start'=>$reserveData['Start'],'End'=>$reserveData['End'],
                                            'CurrencyCode'=>$reserveData['CurrencyCode'],
                                            'TotalAmountAfterTax'=>$reserveData['TotalAmountAfterTax'],
                                            'TotalAmountBeforeTax'=>$reserveData['TotalAmountBeforeTax'],
                                            'TotalDiscount'=>$reserveData['TotalDiscount'],
                                            'TotalExtraCharge'=>$reserveData['TotalExtraCharge'],
                                            'TotalPayment'=>$reserveData['TotalPayment'],
                                            'TACommision'=>$reserveData['TACommision'],
                                            'FirstName'=>$reserveData['FirstName'],
                                            'LastName'=>$reserveData['LastName'],
                                            'Mobile'=>$reserveData['Mobile'],
                                            'Email'=>$reserveData['Email'],
                                            'Country'=>$reserveData['Country'],
                                            'Source'=>$reserveData['Source']
                                        ]);
                                    }
                                    $notifyList[] = [
                                        'BookingId'=>$exist->SubBookingId,
                                        'PMS_BookingId'=>$exist->id
                                    ];
//                                else{
//                                    $exist->update(['Mobile'=>$reserveData['Mobile'],
//                                        'Email'=>$reserveData['Email']]);
//                                }
                                }
                            }
                        }
                    }
                }
            }


                $dataLog = DataLog::create([
                    'title'=>'sendnotify',
                    'data'=>'',
                    'related_id'=>$listing->hotel_code,
                    'status'=>'started'
                ]);
                $postData2['Authentication'] = [
                    'HotelCode'=>$listing->hotel_code,
                    'AuthCode'=>$listing->auth_key
                ];
                $postData2['Request_Type'] = 'BookingRecdNotification';
                $postData2['Bookings'] = ['Booking'=>$notifyList];
                $pd['RES_Request'] = $postData2;
                $payload = json_encode($pd);
                $ch = curl_init();
                curl_setopt(
                    $ch,
                    CURLOPT_URL,
                    "https://live.ipms247.com/pmsinterface/pms_connectivity.php"
                );
                curl_setopt(
                    $ch,
                    CURLOPT_POST,
                    1
                );
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    $payload
                );
                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    array('Content-Type:application/json')
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec($ch);
                curl_close($ch);
                $res = json_decode($server_output, true);
                $dataLog->update(['data'=>$server_output, 'status'=>'sent']);
                if(is_array($res)){
                    foreach($res as $reservation){
                        if(is_array($reservation) && key_exists('Errors',$reservation)){
                            $error = $reservation['Errors'];
                            if($error['ErrorCode'] == "0"){
                                $dataLog->update(['status'=>'success']);
                            }else{
                                $dataLog->update(['status'=>'failed']);
                            }
                        }
                    }
                }
        }
//        $dataLog->update(['status'=>'done']);
    }
}

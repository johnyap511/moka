<?php

namespace App\Http\Controllers\User;


use App\Booking;
use App\BookingApi;
use App\Events\BookingCompletedEvent;
use App\Events\BookingCompleteEZEEAPIEvent;
use App\EzeeGroup;
use App\EzeeGroupListing;
use App\Listing;
use App\ListingCategory;
use App\ListingPrice;
use App\ListingPriceDetail;
use App\OtherModel\BookingDetail;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentProcess(Request $request,$listingId){

//        if(Auth::user()->email != 'greatmemar@yahoo.com'){
//            return back()->with('error', 'The platform is under maintenance please try again later!');
//        }
        $validator = Validator::make($request->all(), [
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'guest' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if(Auth::check() != true){
            return back()->with('error', 'Please login before proceed with payment!');
        }
        $listing = Listing::find($listingId);
        if(empty($listing)){
            return back()->with('error', 'Listing not found!');
        }
        $serviceFee = $listing->cleaning_fee;
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $guest = $request->guest;
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
        $tourismTax = round($tourismTax, 2);
        $pricePerNight = round($totalPrice/$nights, 2);
        $totalPrice = round($totalPrice+$serviceFee+$tourismTax, 2);
        $authUser = Auth::user();

        //Booking Detail
        $listingPD = ListingPriceDetail::where('listing_id', $listing->id)->first();
        $insurance = 0;
        if($listingPD->insurance_has == 1 && $request->insurance == 1){
            if($listingPD->insurance_fixed == 1){
                $insurance = $listingPD->insurance_amount;
            }else{
                $insurance = round(($listingPD->insurance_amount*$totalPrice)/100, 2);
            }
            $totalPrice = $totalPrice+$insurance;

        }
        $bookingDetailData['insurance'] = $insurance;
        if($listingPD->promo_has == 1 && !empty($request->promo) && $request->promo == $listingPD->promo_code){
            $bookingDetailData['promo'] = $listingPD->promo_amount;
            $totalPrice = $totalPrice-$listingPD->promo_amount;
        }



        //Check Availability
        if(!empty($listing->room_type)) {
            $ezeeGroup = EzeeGroupListing::where('listing_id', $listing->id)->first();
            $ezeeGroup = EzeeGroup::where('id', $ezeeGroup->ezee_group_id)->first();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://live.ipms247.com/booking/reservation_api/listing.php?request_type=RoomList&HotelCode=" . $ezeeGroup->hotel_code . "&APIKey=" . $ezeeGroup->auth_key . "&check_in_date=" . $checkIn . "&check_out_date=" . $checkOut);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($server_output, true);
            $correct = false;
            foreach ($res as $result) {
                if (key_exists('Error Details', $result)) {
                    return back()->with('error', $result['Error Details']['Error_Message']);
                }
                if ($result['Room_Name'] == $listing->room_type) {
                    $avas = $result['available_rooms'];
                    foreach ($avas as $ava) {
                        if ($ava < 1) {
                            return back()->with('error', 'The room is not available in selected dates!');
                        }
                    }
                    $correct = true;
                    $Rateplan_Id = $result['roomrateunkid'];
                    $Ratetype_Id = $result['ratetypeunkid'];
                    $Roomtype_Id = $result['roomtypeunkid'];
                }
            }
            if ($correct === false) {
                return back()->with('error', 'The room type is invalid!');
            }
        }

        $booking = Booking::create(['user_id'=>$authUser->id, 'listing_id'=>$listingId, 'check_in'=>$checkIn, 'check_out'=>$checkOut, 'adult'=>$guest, 'infant'=>0,
            'remark'=>null, 'nights'=>$nights, 'price_night'=>$pricePerNight, 'cleaning_fee'=>$serviceFee, 'ota_fee'=>0, 'sst'=>0,
            'price'=>$totalPrice,'tourism_tax'=>$tourismTax,'source'=>'Website','category'=>'Website', 'status'=>1]);

        $bookingDetailData['booking_id'] = $booking->id;
        BookingDetail::create($bookingDetailData);

        if(!empty($listing->room_type)) {
            BookingApi::create(['book_id' => $booking->id, 'listing_id' => $listing->id, 'Rateplan_Id' => $Rateplan_Id, 'Ratetype_Id' => $Ratetype_Id, 'Roomtype_Id' => $Roomtype_Id, 'last_try' => Carbon::now()]);
        }
        if($categoryId > 1){
            $booking->update(['status'=>4]);
            $bookEventData = (['book_id' => $booking->id]);
            Event::dispatch(new BookingCompleteEZEEAPIEvent($bookEventData));
            Event::dispatch(new BookingCompletedEvent($bookEventData));
            return redirect('/user/booking/history')->with('success', 'Reserved successfully!');
        }
        if($request->payment == 1){
            $booking->update(['status'=>4]);
            $bookEventData = (['book_id' => $booking->id]);
            Event::dispatch(new BookingCompleteEZEEAPIEvent($bookEventData));
            Event::dispatch(new BookingCompletedEvent($bookEventData));
            return redirect('/user/booking/history')->with('success', 'Booked successfully!');
        }


        $amount = round($totalPrice*100);

        $key = env('BILLPLZ_KEY');
        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($key)
        );

        $data = array(
            'collection_id' => env('BILLPLZ_COLLECTION_ID'),
            'description' => 'Payment by userId #'.$authUser->id.' for bookId #'.$booking->id,
            'name' => $authUser->name,
            'email' => $authUser->email,
            'amount' => $amount,
            'callback_url' => env('BILLPLZ_CALLBACK_URL'),
            'redirect_url' => env('BILLPLZ_REDIRECT_URL')
        );
        $payload = json_encode($data);

        $ch=curl_init();
//        curl_setopt($ch,CURLOPT_URL,'https://www.billplz-sandbox.com/api/v3/bills');
        curl_setopt($ch,CURLOPT_URL,'https://www.billplz.com/api/v3/bills');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);

        if(!empty($result->error) && !empty($result->error->message)){
            return back()->with('error', 'Failed!');
        }

        Transaction::create(['user_id'=>$authUser->id,'booking_id'=>$booking->id,'bank_id'=>null,'bill_id'=>$result->id,'amount'=>$amount/100,'request_location'=>1,'status'=>2]);
        return redirect()->away($result->url);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentRedirect(Request $request){
        $returnData = $request->billplz;
        $billId = $returnData['id'];
        $transaction = Transaction::where('bill_id', $billId)->first();
        if(empty($billId) || empty($transaction)){
            return redirect('/home')->with('error', 'Payment is invalid!');
        }

        if($returnData['paid'] !== 'true'){
            $book = Booking::where('id', $transaction->booking_id)->first();
            return redirect('/property/detail/'.$book->listing_id.'/check?check_in='.$book->check_in.'&check_out='.$book->check_out.'&guest='.$book->adult)->with('error', 'Payment is failed!');
        }

        $returnSignature = $returnData['x_signature'];
        $string = 'billplzid'.$billId.'|billplzpaid_at'.$returnData['paid_at'].'|billplzpaidtrue';
        $secret = env('BILLPLZ_SECRET');
        $sig = hash_hmac('sha256', $string, $secret);
        if($sig !== $returnSignature){
            $book = Booking::where('id', $transaction->booking_id)->first();
            return redirect('/property/detail/'.$book->listing_id.'/check?check_in='.$book->check_in.'&check_out='.$book->check_out.'&guest='.$book->adult)->with('error', 'Payment is failed!');
        }


        if($transaction->status == 11 ){
            return redirect('/user/booking/history')->with('success', 'Paid successfully!');
        }
        $transaction->update(['status'=>8]);
        $booking = Booking::where('id', $transaction->booking_id)->first();
        if(!empty($booking) && $booking->status == 1){
            $booking->update(['status'=>3]);
            $transaction->update(['status'=>11]);
            $bookEventData = (['book_id' => $booking->id]);
            Event::dispatch(new BookingCompleteEZEEAPIEvent($bookEventData));
            Event::dispatch(new BookingCompletedEvent($bookEventData));
        }
        return redirect('/user/booking/history')->with('success', 'Paid successfully!');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentCallback(Request $request){
        $billId = $request->id;
        $transaction = Transaction::where('bill_id', $billId)->first();
        if(empty($billId) || empty($transaction)){
            return response()->json(['statusCode' => '200', 'respond'=>'Payment is invalid!'], 200);
        }
        $transaction->update(['remark'=>'Existing!']);

        if($request->paid !== 'true'){
            $transaction->update(['remark'=>'Not paid!']);
            return response()->json(['statusCode' => '200', 'respond'=>'Payment is failed!'], 200);
        }

        $returnSignature = $request->x_signature;
        $string = 'amount'.$request->amount.'|collection_id'.$request->collection_id.'|due_at'.$request->due_at.'|email'.$request->email.'|id'.$request->id.'|mobile'.$request->mobile.'|name'.$request->name.'|paid_amount'.$request->paid_amount.'|paid_at'.$request->paid_at.'|paid'.$request->paid.'|state'.$request->state.'|url'.$request->url;
        $secret = env('BILLPLZ_SECRET');
        $sig = hash_hmac('sha256', $string, $secret);
        if($sig !== $returnSignature){
            $transaction->update(['remark'=>'Wrong Signature!']);
            return response()->json(['statusCode' => '200', 'respond'=>'Failed!'], 200);
        }

        if($transaction->status == 11 ){
            return response()->json(['statusCode' => '200', 'respond'=>'Paid successfully!'], 200);
        }else{
            $transaction->update(['status'=>8]);
            $booking = Booking::where('id', $transaction->booking_id)->first();
            if(!empty($booking) && $booking->status == 1){
                $booking->update(['status'=>3]);
                $transaction->update(['status'=>11]);
                $bookEventData = (['book_id' => $booking->id]);
                Event::dispatch(new BookingCompleteEZEEAPIEvent($bookEventData));
                Event::dispatch(new BookingCompletedEvent($bookEventData));
            }
        }
        return response()->json(['statusCode' => '200', 'respond'=>'Paid successfully!'], 200);
    }
}

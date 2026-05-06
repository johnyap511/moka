<?php

namespace App\Http\Controllers\API;

use App\AccessToken;
use App\Http\Controllers\Controller;
use App\Inventory;
use App\Listing;
use App\RateLinear;
use App\RateNonLinear;
use App\StopSell;
use Illuminate\Http\Request;

class EZEEAPIController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function linearRate(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $dataArray = json_decode($json, true);
        if (array_key_exists('Request_Type', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                    <RES_Response><Errors><ErrorCode>309</ErrorCode>
                            <ErrorMessage>Invalid Data</ErrorMessage></Errors>
                    </RES_Response>';
        }
        if ($dataArray['Request_Type'] != 'UpdateRoomRates') {
            return '<?xml version="1.0" standalone="yes"?>
                    <RES_Response><Errors><ErrorCode>310</ErrorCode>
                            <ErrorMessage>Invalid Request!</ErrorMessage></Errors>
                    </RES_Response>';
        }
        if (array_key_exists('Authentication', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                    <RES_Response><Errors><ErrorCode>311</ErrorCode>
                        <ErrorMessage>Authentication is required!</ErrorMessage></Errors>
                    </RES_Response>';
        }
        $auth = $dataArray['Authentication'];
        if (array_key_exists('AuthCode', $auth) == false) {
            return '<?xml version="1.0" standalone="yes"?>
            <RES_Response>
                <Errors><ErrorCode>312</ErrorCode>
                <ErrorMessage>Authentication Code is required!</ErrorMessage></Errors>
            </RES_Response>';
        }

        $accessToken = AccessToken::where([
            ['token', sha1($auth['AuthCode'])],
            ['name', 'EZEE_API']
            ])->first();
        if (empty($accessToken)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>313</ErrorCode>
                    <ErrorMessage>Invalid Authentication Code!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('HotelCode', $auth) == false || empty($auth['HotelCode'])) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>314</ErrorCode>
                    <ErrorMessage>HotelCode is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $listing = Listing::where('ezee_hotel_code', $auth['HotelCode'])->first();
        if (empty($listing)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>315</ErrorCode>
                    <ErrorMessage>Invalid HotelCode!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($listing->id != $accessToken->tokenable_id) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>316</ErrorCode>
                    <ErrorMessage>Invalid Access!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $rates = $dataArray['RateType'];
        $roomRate = $rates['RoomRate'];
        RateLinear::create([
                'listing_id' => $listing->id,
                'room_type_id' => $rates['RoomTypeID'], 
                'rate_type_id' => $rates['RateTypeID'], 
                'from_date' => $rates['FromDate'],
                'to_date' => $rates['ToDate'], 
                'base_rate' => $roomRate['Base'], 
                'extra_adult_rate' => $roomRate['ExtraAdult'],
                'extra_child_rate' => $roomRate['ExtraChild']
            ]);

        return '<?xml version="1.0" encoding="UTF-8"?><RES_Response><Success>
        <SuccessMsg>Room Rates Updated successfully.</SuccessMsg></Success></RES_Response>';
    }

    /**
     * @param Request $request
     * @return string
     */
    public function nonLinearRate(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $dataArray = json_decode($json, true);
        if (array_key_exists('Request_Type', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>309</ErrorCode>
                    <ErrorMessage>Invalid Data</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($dataArray['Request_Type'] != 'UpdateRoomRatesNL') {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>310</ErrorCode>
                    <ErrorMessage>Invalid Request!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('Authentication', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>311</ErrorCode>
                    <ErrorMessage>Authentication is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $auth = $dataArray['Authentication'];
        if (array_key_exists('AuthCode', $auth) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>312</ErrorCode>
                   <ErrorMessage>Authentication Code is required!</ErrorMessage></Errors>
                </RES_Response>';
        }

        $accessToken = AccessToken::where(
            [
                [
                    'token', sha1($auth['AuthCode']),
                ],
                ['name', 'EZEE_API'],
            ])->first();
        if (empty($accessToken)) {
            return '<?xml version="1.0" standalone="yes"?>
                    <RES_Response><Errors><ErrorCode>313</ErrorCode>
                        <ErrorMessage>Invalid Authentication Code!</ErrorMessage></Errors>
                    </RES_Response>';
        }
        if (array_key_exists('HotelCode', $auth) == false || empty($auth['HotelCode'])) {
            return '<?xml version="1.0" standalone="yes"?>
            <RES_Response><Errors><ErrorCode>314</ErrorCode>
                    <ErrorMessage>HotelCode is required!</ErrorMessage></Errors>
            </RES_Response>';
        }
        $listing = Listing::where('ezee_hotel_code', $auth['HotelCode'])->first();
        if (empty($listing)) {
            return '<?xml version="1.0" standalone="yes"?>
            <RES_Response><Errors><ErrorCode>315</ErrorCode>
                    <ErrorMessage>Invalid HotelCode!</ErrorMessage></Errors>
            </RES_Response>';
        }
        if ($listing->id != $accessToken->tokenable_id) {
            return '<?xml version="1.0" standalone="yes"?>
                    <RES_Response>
                        <Errors><ErrorCode>316</ErrorCode>
                        <ErrorMessage>Invalid Access!</ErrorMessage></Errors>
                    </RES_Response>';
        }
        $rates = $dataArray['RateType'];
        $roomRate = $rates['RoomRate'];
        RateNonLinear::create(
            [
                'listing_id' => $listing->id,
                'room_type_id' => $rates['RoomTypeID'],
                'rate_type_id' => $rates['RateTypeID'],
                'from_date' => $rates['FromDate'],
                'to_date' => $rates['ToDate'],
                'rate_adult_1' => $roomRate['Adult1'],
                'rate_adult_2' => $roomRate['Adult2'],
                'rate_adult_3' => $roomRate['Adult3'],
                'rate_adult_4' => $roomRate['Adult4'],
                'rate_adult_5' => $roomRate['Adult5'],
                'rate_adult_6' => $roomRate['Adult6'],
                'rate_adult_7' => $roomRate['Adult7'],
                'rate_child_1' => $roomRate['Child1'],
                'rate_child_2' => $roomRate['Child2'],
                'rate_child_3' => $roomRate['Child3'],
                'rate_child_4' => $roomRate['Child4'],
                'rate_child_5' => $roomRate['Child5'],
                'rate_child_6' => $roomRate['Child6'],
                'rate_child_7' => $roomRate['Child7'],
            ]
        );
        return '<?xml version="1.0" encoding="UTF-8"?><RES_Response><Success>
        <SuccessMsg>Room Rates Updated successfully.</SuccessMsg></Success></RES_Response>';
    }

    /**
     * @param Request $request
     * @return string
     */
    public function inventory(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $dataArray = json_decode($json, true);
        if (array_key_exists('Request_Type', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>309</ErrorCode>
                    <ErrorMessage>Invalid Data</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($dataArray['Request_Type'] != 'UpdateAvailability') {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>310</ErrorCode>
                    <ErrorMessage>Invalid Request!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('Authentication', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>311</ErrorCode>
                    <ErrorMessage>Authentication is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $auth = $dataArray['Authentication'];
        if (array_key_exists('AuthCode', $auth) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>312</ErrorCode>
                    <ErrorMessage>Authentication Code is required!</ErrorMessage></Errors>
                </RES_Response>';
        }

        $accessToken = AccessToken::where([['token', sha1($auth['AuthCode'])], ['name', 'EZEE_API']])->first();
        if (empty($accessToken)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>313</ErrorCode>
                    <ErrorMessage>Invalid Authentication Code!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('HotelCode', $auth) == false || empty($auth['HotelCode'])) {
            return '<?xml version="1.0" standalone="yes"?>
            <RES_Response><Errors><ErrorCode>314</ErrorCode>
                    <ErrorMessage>HotelCode is required!</ErrorMessage></Errors>
            </RES_Response>';
        }
        $listing = Listing::where('ezee_hotel_code', $auth['HotelCode'])->first();
        if (empty($listing)) {
            return '<?xml version="1.0" standalone="yes"?>
            <RES_Response><Errors><ErrorCode>315</ErrorCode>
                    <ErrorMessage>Invalid HotelCode!</ErrorMessage></Errors>
            </RES_Response>';
        }
        if ($listing->id != $accessToken->tokenable_id) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>316</ErrorCode>
                    <ErrorMessage>Invalid Access!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $roomType = $dataArray['RoomType'];
        Inventory::create([
            'listing_id' => $listing->id,
            'room_type_id' => $roomType['RoomTypeID'], 
            'from_date' => $roomType['FromDate'],
            'to_date' => $roomType['ToDate'],
            'availability' => $roomType['Availability']
        ]);
        return '<?xml version="1.0" encoding="UTF-8"?><RES_Response><Success>
        <SuccessMsg>Room Rates Updated successfully.</SuccessMsg></Success></RES_Response>';

    }

    /**
     * @param Request $request
     * @return string
     */
    public function stopsell(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $dataArray = json_decode($json, true);
        if (array_key_exists('Request_Type', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>309</ErrorCode>
                    <ErrorMessage>Invalid Data</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($dataArray['Request_Type'] != 'UpdateStopSell') {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>310</ErrorCode>
                    <ErrorMessage>Invalid Request!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('Authentication', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>311</ErrorCode>
                    <ErrorMessage>Authentication is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $auth = $dataArray['Authentication'];
        if (array_key_exists('AuthCode', $auth) == false) {
            return '<?xml version="1.0" standalone="yes"?>
            <RES_Response><Errors><ErrorCode>312</ErrorCode>
                    <ErrorMessage>Authentication Code is required!</ErrorMessage></Errors>
            </RES_Response>';
        }

        $accessToken = AccessToken::where([['token', sha1($auth['AuthCode'])], ['name', 'EZEE_API']])->first();
        if (empty($accessToken)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>313</ErrorCode>
                    <ErrorMessage>Invalid Authentication Code!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('HotelCode', $auth) == false || empty($auth['HotelCode'])) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>314</ErrorCode>
                    <ErrorMessage>HotelCode is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $listing = Listing::where('ezee_hotel_code', $auth['HotelCode'])->first();
        if (empty($listing)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>315</ErrorCode>
                    <ErrorMessage>Invalid HotelCode!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($listing->id != $accessToken->tokenable_id) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>316</ErrorCode>
                    <ErrorMessage>Invalid Access!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $ratePlan = $dataArray['RatePlan'];
        StopSell::create(
            [
                'listing_id' => $listing->id,
                'room_type_id' => $ratePlan['RoomTypeID'],
                'rate_type_id' => $ratePlan['RateTypeID'],
                'from_date' => $ratePlan['FromDate'],
                'to_date' => $ratePlan['ToDate'],
                'stopsell' => $ratePlan['StopSell'],
            ]
        );
        return '<?xml version="1.0" encoding="UTF-8"?><RES_Response><Success>
        <SuccessMsg>Room Rates Updated successfully.</SuccessMsg></Success></RES_Response>';
    }

    /**
     * @param Request $request
     * @return string
     */
    public function bookingDetail(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $dataArray = json_decode($json, true);
        if (array_key_exists('Request_Type', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>309</ErrorCode>
                    <ErrorMessage>Invalid Data</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($dataArray['Request_Type'] != 'UpdateStopSell') {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>310</ErrorCode>
                    <ErrorMessage>Invalid Request!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('Authentication', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>311</ErrorCode>
                    <ErrorMessage>Authentication is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $auth = $dataArray['Authentication'];
        if (array_key_exists('AuthCode', $auth) == false) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>312</ErrorCode>
                    <ErrorMessage>Authentication Code is required!</ErrorMessage></Errors>
                </RES_Response>';
        }

        $accessToken = AccessToken::where([['token', sha1($auth['AuthCode'])], ['name', 'EZEE_API']])->first();
        if (empty($accessToken)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>313</ErrorCode>
                    <ErrorMessage>Invalid Authentication Code!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if (array_key_exists('HotelCode', $auth) == false || empty($auth['HotelCode'])) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>314</ErrorCode>
                    <ErrorMessage>HotelCode is required!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $listing = Listing::where('ezee_hotel_code', $auth['HotelCode'])->first();
        if (empty($listing)) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>315</ErrorCode>
                    <ErrorMessage>Invalid HotelCode!</ErrorMessage></Errors>
                </RES_Response>';
        }
        if ($listing->id != $accessToken->tokenable_id) {
            return '<?xml version="1.0" standalone="yes"?>
                <RES_Response><Errors><ErrorCode>316</ErrorCode>
                    <ErrorMessage>Invalid Access!</ErrorMessage></Errors>
                </RES_Response>';
        }
        $ratePlan = $dataArray['RatePlan'];
            StopSell::create([
                'listing_id' => $listing->id,
                'room_type_id' => $ratePlan['RoomTypeID'],
                'rate_type_id' => $ratePlan['RateTypeID'],
                'from_date' => $ratePlan['FromDate'],
                'to_date' => $ratePlan['ToDate'],
                'stopsell' => $ratePlan['StopSell'],
            ]);
        return '<?xml version="1.0" encoding="UTF-8"?><RES_Response><Success>
        <SuccessMsg>Room Rates Updated successfully.</SuccessMsg></Success></RES_Response>';
    }

    /**
     * @param Request $request
     * @return string
     */
    public function bookingCreate(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $dataArray = json_decode($json, true);
        if (array_key_exists('Reservations', $dataArray) == false) {
            return '<?xml version="1.0" standalone="yes"?><RES_Response><Errors><ErrorCode>309</ErrorCode>
                    <ErrorMessage>Invalid Data</ErrorMessage></Errors></RES_Response>';
        }
        foreach ($xml->Reservations as $reservation) {
            if (empty($reservation->Reservation)) {
                return '<?xml version="1.0" standalone="yes"?><RES_Response><Errors><ErrorCode>310</ErrorCode>
                    <ErrorMessage>Reservation is missing!</ErrorMessage></Errors></RES_Response>';
            }
            if (empty($reservation->Reservation->BookByInfo)) {
                return '<?xml version="1.0" standalone="yes"?><RES_Response><Errors><ErrorCode>311</ErrorCode>
                    <ErrorMessage>BookByInfo is missing!</ErrorMessage></Errors></RES_Response>';
            }
            $BookingTran = $reservation->Reservation->BookByInfo->BookingTran;
            foreach ($BookingTran as $reserveData) {
                $exist = \App\OtherModel\EzeeBooking::where([['SubBookingId', $reserveData->SubBookingId], ['TransactionId', $reserveData->TransactionId]])->first();
                if (empty($exist)) {
                    $exist = \App\OtherModel\EzeeBooking::create(
                        ['SubBookingId' => $reserveData->SubBookingId,
                        'TransactionId' => $reserveData->TransactionId,
                        'IsConfirmed' => $reserveData->IsConfirmed, 
                        'RateplanName' => $reserveData->RateplanName, 
                        'RoomTypeName' => $reserveData->RoomTypeName,
                        'Start' => $reserveData->Start, 
                        'End' => $reserveData->End, 
                        'CurrencyCode' => $reserveData->CurrencyCode,
                        'TotalAmountAfterTax' => $reserveData->TotalAmountAfterTax, 'TotalAmountBeforeTax' => $reserveData->TotalAmountBeforeTax,
                        'TotalDiscount' => $reserveData->TotalDiscount, 
                        'TotalExtraCharge' => $reserveData->TotalExtraCharge, 
                        'TotalPayment' => $reserveData->TotalPayment,
                        'TACommision' => $reserveData->TACommision, 
                        'FirstName' => $reserveData->FirstName, 
                        'LastName' => $reserveData->LastName, 
                        'Mobile' => $reserveData->Mobile,
                        'Email' => $reserveData->Email, 
                        'Country' => $reserveData->Country, 
                        'Source' => $reserveData->Source
                    ]);
                }
            }
        }
        return '<?xml version="1.0" encoding="UTF-8"?><RES_Response><Success>
        <SuccessMsg>Room Rates Updated successfully.</SuccessMsg></Success></RES_Response>';
    }
}

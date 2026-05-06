<?php

namespace App\Import;

use App\Booking;
use App\Listing;
use App\Mail\BookingStatusMail;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BookingImport implements ToCollection, WithHeadingRow
{
    private
//        $courseId, $intakeId,
        $getResData;

//    public function __construct(int $courseId, int $intakeId)
//    {
//        $this->courseId = $courseId;
//        $this->intakeId = $intakeId;
//    }

    /**
     * @param array $row
     *
     * @return User|null
     */
    public function collection(Collection $rows)
    {
        $dataRejects = [];
        $dataSuccesses = [];
//        $courseId = $this->courseId;
        $now = Carbon::now();
        $i=0;
        foreach ($rows as $row)
        {
            $i++;
            if(!isset($row["listing_name"]) || empty($row["listing_name"])){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "Listing Name field incomplete.";
                continue;
            }
       
            if(!isset($row["user_email"]) || empty($row["user_email"])){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "User Email field incomplete.";
                continue;
            }

            if(!isset($row["check_in"]) || empty($row["check_in"])){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "Check In field incomplete.";
                continue;
            }

            if(!isset($row["check_out"]) || empty($row["check_out"])){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "Check Out field incomplete.";
                continue;
            }

            if(!isset($row["price_per_night"]) || empty($row["price_per_night"])){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "Price Per Night field incomplete.";
                continue;
            }


            $listingName = $row["listing_name"];
            $userEmail = $row["user_email"];

            $listing = Listing::where('name', $listingName)->first();
            if(empty($listing)){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "Listing with this name does not exist.";
                continue;
            }

            if($row['check_out']<=$row['check_in']){
                $dataRejects[$i]['data'] = $row;
                $dataRejects[$i]['error'] = "Check in date can not be after check out date.";
                continue;
            }

            $today = date("Y-m-d");
            $books = Booking::where([['listing_id', $listing->id], ['status', 5], ['check_out', '>=', $today]])->get();
            $exist = false;
            foreach($books as $book){
                if($book->check_in<$row['check_out'] && $book->check_out>$row['check_in']){
                    $dataRejects[$i]['data'] = $row;
                    $dataRejects[$i]['error'] = "The listing is booked in these dates.";
                    $exist = true;
                    break;
                }
            }
            if($exist == true){
                continue;
            }

            $user = User::where('email', $userEmail)->first();
            if(empty($user)){
                $userData = [];
                $userData['name'] = '';
                $userData['email'] = $userEmail;
                if(isset($row["user_first_name"]) && !empty($row["user_first_name"])){
                    $userData['name'] = $row["user_first_name"];
                }
                if(isset($row["user_last_name"]) && !empty($row["user_last_name"])){
                    $userData['last_name'] = $row["user_last_name"];
                }
                if(isset($row["user_phone"]) && !empty($row["user_phone"])){
                    $userData['phone'] = $row["user_phone"];
                }
                $user = User::create($userData);
                $role = Role::find(2);
                $user->attachRole($role);
            }

            $bookData['adult'] = 2;
            $bookData['infant'] = 0;
            if(isset($row["adult"]) && !empty($row["adult"])){
                $bookData['adult'] = $row["adult"];
            }

            if(isset($row["infant"]) && !empty($row["infant"])){
                $bookData['infant'] = $row["infant"];
            }

            if(isset($row["remark"]) && !empty($row["remark"])){
                $bookData['remark'] = $row["remark"];
            }

            $bookingStartDate = date_create($row['check_in']);
            $bookingEndDate = date_create($row['check_out']);

            $interval = date_diff($bookingStartDate, $bookingEndDate);
            $bookData['nights'] = $interval->format('%a');
            $bookData['price'] = $row["price_per_night"]*$bookData['nights'];
            $bookData['check_in'] = date_format($bookingStartDate, 'Y-m-d');
            $bookData['check_out'] = date_format($bookingEndDate, 'Y-m-d');
            $bookData['user_id'] = $user->id;
            $bookData['listing_id'] = $listing->id;

            $bookData['status'] = 5;
            Booking::create($bookData);

            $data22['status'] = 5;
            $data22['name'] = $user->name;
            $data22['check_in'] = $bookData['check_in'];
            $data22['check_out'] = $bookData['check_out'];
            $data22['listing_name'] = $listing->name;
//            Mail::to($user->email)->queue(new BookingStatusMail($data22));


            $dataSuccesses[$i]['data'] = $row;
            $dataSuccesses[$i]['success'] = "success";
            continue;
        }

        $data['success'] = $dataSuccesses;
        $data['reject'] = $dataRejects;
        $this->getResData = $data;
    }

    public function getResData(): array
    {
        return $this->getResData;
    }

}
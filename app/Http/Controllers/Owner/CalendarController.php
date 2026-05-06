<?php

namespace App\Http\Controllers\Owner;

use App\Booking;
use App\Http\Controllers\Controller;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CalendarController extends Controller
{
    public function index($id)
    {
        $listing = Listing::where('user_id', Auth::user()->id)->find($id);
        $list = '';
        if ($listing) {
            $list = $listing->id;
        } else {
            $list = '';
        }
        // dd($list);
        $books = Booking::where([['listing_id', $list], ['status', '>', 1]])->get();
        $events = [];
        foreach ($books as $book) {
            $name = '';
            $ezeeBook = EzeeBooking::where('book_id', $book->id)->first();
            if (!empty($ezeeBook)) {
                $name = $ezeeBook->FirstName . ' ' . $ezeeBook->LastName;
            }
            if (empty($name)) {
                $user = User::find($book->user_id);
                if (!empty($user)) {
                    $name = $user->name . ' ' . $user->last_name;
                }
            }

            $exist = false;
            foreach ($events as $event) {
                if ($name == $event['title'] && $book->check_in == $event['start'] && $book->check_out == $event['end']) {
                    $exist = true;
                }
            }
            if ($exist === false) {
                $guest = $book->adult . ' adults, ' . $book->infant . ' children';
                $events[] = [
                    'id' => $book->id, 'title' => $name, 'start' => $book->check_in, 'end' => $book->check_out, 'guest' => $guest, 'price_night' => $book->price_night, 'sst' => $book->sst, 'sst_cf' => $book->sst_cf, 'created_at' => $book->created_at,
                    'nights' => $book->nights, 'cleaning_fee' => $book->cleaning_fee, 'ota_fee' => $book->ota_fee, 'price' => $book->price, 'source' => $book->source, 'discount' => $book->discount_fee,
                ];
            }
        }
        $events = json_encode($events);
        return view('owner.listing.calendar', compact('listing', 'events'));
    }
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function allBooks()
    {
        $listingIds = Listing::where('user_id', Auth::user()->id)->pluck('id')->toArray();
        $end_listingIds = reset($listingIds);
        $books = Booking::whereIn('listing_id', [$end_listingIds])->where('status', '>', 1)->get();
        $events = [];
        foreach ($books as $book) {
            $user = User::find($book->user_id);
            $name = '';
            if (!empty($user)) {
                $name = $user->name . ' ' . $user->last_name;
            } else {
                $ezeeBook = EzeeBooking::where('book_id', $book->id)->first();
                if (!empty($ezeeBook)) {
                    $name = $ezeeBook->FirstName . ' ' . $ezeeBook->LastName;
                }
            }
            $guest = $book->adult . ' adults, ' . $book->infant . ' children';
            $events[] = [
                'id' => $book->id, 'title' => 'Booked by ' . $name, 'start' => $book->check_in, 'end' => $book->check_out, 'guest' => $guest, 'price_night' => $book->price_night, 'sst' => $book->sst, 'sst_cf' => $book->sst_cf, 'created_at' => $book->created_at,
                'nights' => $book->nights, 'cleaning_fee' => $book->cleaning_fee, 'discount' => $book->discount_fee, 'ota_fee' => $book->ota_fee, 'price' => $book->price, 'source' => $book->source,
            ];
            // $events[] = ['id'=>$book->id, 'title'=>'Booked by '.$name, 'start'=>$book->check_in, 'end'=>$book->check_out];
        }
        // dd($events);
        $events = json_encode($events);
        return view('owner.listing.calendar', compact('events'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function export(Request $request)
    {
        $week = $request->week;
        if (empty($week)) {
            $week = 1;
        }
        if ($week > 12) {
            return back()->with('error', 'The number of weeks can not be more than 12 weeks!');
        }
        $colors = ['98df99', 'c58878', 'b9b91e', '94b919', '53ccf8', 'dba1d9', 'c98676', '87a3e5', '5fdc5e', '9698d5', '66c168', 'b4abc4', 'c0ab4b', '49ab84', '7dabc2'];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $weekDay = date('w');
        $start = Carbon::now()->modify('-' . $weekDay . ' days');
        $dif = 6 - $weekDay + (($week - 1) * 7);
        $end = Carbon::now()->modify('+' . $dif . ' days');

        $listings = Listing::where('user_id', Auth::user()->id)->where('status', 1)->get();
        // dd($listings);
        $alphabet = range('A', 'Z');
        $alphas = range('A', 'Z');

        for ($i = 0; $i <= $week; $i++) {
            foreach ($alphas as $alpha) {
                $alphabet[] = $alphas[$i] . $alpha;
            }
        }

        $exportData[0] = ['', ''];
        $exportData[1] = ['', ''];
        $exportData[2] = ['Property', 'Owner'];
        $x = 0;
        $colNo = 2;
        while ($start <= $end) {
            $dateArray[$x][0] = date_format($start, 'Y-m-d');
            $dateArray[$x][1] = $alphabet[$colNo];
            $dateArray[$x][2] = $colNo;
            $dateArray[$x][3] = $alphabet[$colNo + 1];
            $exportData[0][] = date_format($start, 'F');
            $exportData[0][] = date_format($start, 'F');
            $exportData[1][] = date_format($start, 'D jS');
            $exportData[1][] = date_format($start, 'D jS');
            $exportData[2][] = "AM";
            $exportData[2][] = "PM";
            $start->modify('+1 day');
            $x++;
            $colNo = $colNo + 2;
        }

        $sheet->fromArray(
            $exportData,
            null,
            'A1'
        );

        $oldVal = 'X';
        $oldCoordinate = 'A1';
        $index = 1;
        foreach ($sheet->getRowIterator() as $row) {
            // dd($row);
            $cellIterator = $row->getCellIterator();
            foreach ($cellIterator as $cell) {
                if (!empty($cell) && $row->getRowIndex() == $index && $cell->getValue() == $oldVal) {
                    $sheet->mergeCells($oldCoordinate . ':' . $cell->getCoordinate());
                }
                $oldCoordinate = $cell->getCoordinate();
                $oldVal = $cell->getValue();
                $index = $row->getRowIndex();
            }
        }

        $start = Carbon::now()->modify('-' . $weekDay . ' days');
        // dd($start,$end);
        $i = 0;
        $curRow = 4;
        $lastDay = end($dateArray);
        foreach ($listings as $listing) {
            $owner = User::where('id', $listing->user_id)->first();
            $name = '#' . $listing->id . ' ' . $listing->name;
            if (!empty($owner)) {
                $ownerName = $owner->name . ' ' . $owner->last_name;
            } else {
                $ownerName = '';
            }
            $sheet->setCellValue('A' . $curRow, $name);
            $sheet->setCellValue('B' . $curRow, $ownerName);
            $books = Booking::where([['listing_id', $listing->id], ['check_out', '>=', $start], ['check_in', '<=', $end]])->get();
            // dd($books);
            foreach ($books as $book) {
                $checkIn = $book->check_in;
                $checkOut = $book->check_out;
                // dd($checkIn);
                $checked = false;
                $checkInCoor = "";
                foreach ($dateArray as $date22) {
                    if ($checked === false && $checkIn <= $date22[0] && $checkOut > $date22[0]) {
                        if ($checkIn < $date22[0]) {
                            $checkInCoor = $date22[1];
                        } else {
                            $checkInCoor = $date22[3];
                        }
                        $checked = true;
                        $bookedBy = User::find($book->user_id);
                        if (!empty($bookedBy)) {
                            $bookedBy = $bookedBy->name . ' ' . $bookedBy->last_name;
                        }
                        $sheet->setCellValue($checkInCoor . $curRow, $bookedBy);
                        $sheet->getStyle($checkInCoor . $curRow)->getFill()->getStartColor()->setRGB('FFFF0000');
                    }
                    if ($checked === true) {
                        //end
                        if ($checkOut <= $date22[0]) {
                            $range = $checkInCoor . $curRow . ':' . $date22[1] . $curRow;
                            $sheet->mergeCells($range);
                            //                    echo $checkInCoor.$curRow.':'.$date22[1].$curRow.'<br />';
                            $picked = $colors[array_rand($colors)];
                            $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($picked);
                            break;
                        } elseif ($lastDay[0] === $date22[0]) {
                            $range = $checkInCoor . $curRow . ':' . $date22[3] . $curRow;
                            $sheet->mergeCells($range);
                            $picked = $colors[array_rand($colors)];
                            $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($picked);
                            break;
                        }
                    }
                }
                if (!empty($checkInCoor)) {
                    //                echo $checkInCoor.$curRow.'<br />';
                    //                $sheet->setCellValue($checkInCoor.$curRow, 'PhpSpreadsheet');
                }
            }

            $curRow++;
            $i++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        ob_end_clean();
        $extension = 'Xlsx';
        $writer = IOFactory::createWriter($spreadsheet, $extension);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"Calendar.{$extension}\"");
        $writer->save('php://output');
        exit();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $book = Booking::find($id);
        $user = User::find($book->user_id);
        $listing = Listing::find($book->listing_id);
        $owner = User::find($listing->user_id);
        return view('owner.listing.book.detail', compact('book', 'user', 'listing', 'owner'));
    }
}

<?php

namespace App\Export;

use App\Booking;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class BookingExport implements FromCollection
{
    public function collection()
    {
        return Booking::where('status', '>=', 3)->get();
    }
}
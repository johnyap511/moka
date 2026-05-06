<?php

namespace App\Export;

use App\Listing;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;

class OwnerListingExport implements FromCollection
{
    public function collection()
    {
        return Listing::where('user_id',Auth::id())->get();
    }
}
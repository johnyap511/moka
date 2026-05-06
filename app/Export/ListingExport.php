<?php

namespace App\Export;

use App\Listing;
use Maatwebsite\Excel\Concerns\FromCollection;

class ListingExport implements FromCollection
{
    public function collection()
    {
        return Listing::all();
    }
}
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingCategory extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'listing_categories';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id', 'category_id',
        //Category ID:
        //1. short term rental (daily/weekly)
        //2. medium term rental (3 months and above)
        //3. long term rental (1 year and above)
    ];

}

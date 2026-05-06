<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingPrice extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'listing_prices';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id', 'date', 'price',
    ];
}

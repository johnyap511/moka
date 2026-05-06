<?php

namespace App\OtherModel;

use Illuminate\Database\Eloquent\Model;

class ListingZone extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'listing_zones';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id', 'zone_id'
    ];
}

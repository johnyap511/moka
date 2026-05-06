<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StopSell extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'stop_sells';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id','room_type_id','rate_type_id','from_date','to_date','stopsell'
    ];
}

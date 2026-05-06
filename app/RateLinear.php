<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateLinear extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'rate_linears';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id','room_type_id','rate_type_id','from_date','to_date','base_rate','extra_adult_rate','extra_child_rate','status',
    ];

}

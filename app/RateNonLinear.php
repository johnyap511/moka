<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateNonLinear extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'rate_non_linears';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id','room_type_id','rate_type_id','from_date','to_date','rate_adult_1','rate_adult_2','rate_adult_3','rate_adult_4','rate_adult_5',
        'rate_adult_6','rate_adult_7','rate_child_1','rate_child_2','rate_child_3','rate_child_4','rate_child_5','rate_child_6','rate_child_7','status',
    ];
}

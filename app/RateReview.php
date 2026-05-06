<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateReview extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'rate_reviews';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id','user_id','booking_id','listing_rate','listing_review','status'
    ];
}

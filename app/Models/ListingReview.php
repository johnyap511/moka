<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingReview extends Model
{
    protected $fillable = [
        'listing_id', 'reviewer_name', 'reviewer_avatar',
        'rating', 'comment', 'stay_date',
    ];

    protected $casts = [
        'stay_date' => 'date',
    ];
}

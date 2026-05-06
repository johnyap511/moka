<?php

namespace App\OtherModel;

use Illuminate\Database\Eloquent\Model;

class BookingDetail extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'booking_details';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'booking_id','insurance','discount','promo','advance_rental','security_deposit','utility_deposit'
    ];
}

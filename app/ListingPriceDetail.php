<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingPriceDetail extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'listing_price_details';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id','discount_has','discount_fixed','discount_amount','insurance_has','insurance_fixed','insurance_amount','promo_has','promo_code','promo_amount',
        'advance_rental','security_deposit','utility_deposit'
    ];
}

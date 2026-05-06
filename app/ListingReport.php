<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingReport extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'listing_reports';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'listing_id','year','month','excel_name','water','water_amount','water_receipt','internet','internet_amount','internet_receipt','electricity','electricity_amount',
        'electricity_receipt','adjustment1','adjustment1_text','adjustment1_amount','adjustment1_receipt','adjustment2','adjustment2_text','adjustment2_amount',
        'adjustment2_receipt','adjustment3','adjustment3_text','adjustment3_amount','adjustment3_receipt','owner_income','platform_income'
    ];
}

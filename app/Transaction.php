<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id','booking_id','bank_id','bill_id','amount','request_location','status','remark'
        //status = 2->created  1->failed 8->paid successful 11->booking updated
        //request_location 1->booking
    ];
}

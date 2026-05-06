<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id','report_id','listing_id','amount','receipt','status','remark',
    ];

    //status 1->pending 8->paid
}

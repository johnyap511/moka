<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingDetail extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'listing_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'listing_id', 'host_name','host_contact','description','distances','during_stay',
    ];
}

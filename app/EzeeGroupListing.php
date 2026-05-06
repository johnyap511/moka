<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeGroupListing extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ezee_group_listings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ezee_group_id', 'listing_id'
    ];
}

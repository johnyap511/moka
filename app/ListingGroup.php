<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingGroup extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'listing_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'listing_id', 'group_id','group_type_id'
    ];
}

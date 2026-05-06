<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeGroup extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ezee_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'hotel_code', 'auth_key'
    ];
}

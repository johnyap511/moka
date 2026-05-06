<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Amenities extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'amenities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'image', 'order', 'chinese', 'malay',
    ];



}

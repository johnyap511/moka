<?php

namespace App\OtherModel;

use Illuminate\Database\Eloquent\Model;

class PropertyEstimate extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'property_estimates';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'address', 'email', 'phone', 'bedroom', 'type', 'status'
    ];
}

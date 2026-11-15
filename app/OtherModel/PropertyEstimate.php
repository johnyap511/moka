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
        'name', 'address', 'email', 'phone', 'bedroom', 'type', 'status'
    ];

    // status: 1 = new (not yet contacted), 2 = followed up.
    const STATUS_NEW = 1;
    const STATUS_FOLLOWED_UP = 2;

    protected $casts = ['followed_up_at' => 'datetime'];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'verifications';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id', 'user', 'code', 'last_try', 'tries',
    ];

}

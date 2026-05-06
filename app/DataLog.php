<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DataLog extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'data_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'related_id', 'title','data', 'status'
    ];
}

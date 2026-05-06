<?php

namespace App\OtherModel;

use Illuminate\Database\Eloquent\Model;

class GroupType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'group_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'type','weight'
    ];
}

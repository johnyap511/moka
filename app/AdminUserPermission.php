<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminUserPermission extends Model
{
    protected $table = 'admin_user_permissions';

    protected $guarded = [];

    protected $casts = [
        'granted' => 'boolean',
    ];
}

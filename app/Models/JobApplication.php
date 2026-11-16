<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'role', 'role_title', 'link', 'message',
        'cv_path', 'cv_name', 'ip', 'spam', 'spam_reason',
    ];

    protected $casts = ['spam' => 'boolean'];
}

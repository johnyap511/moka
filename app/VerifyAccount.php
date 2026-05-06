<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VerifyAccount extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'verify_accounts';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id', 'document_type', 'document_number', 'document_image', 'status',
    ];

}

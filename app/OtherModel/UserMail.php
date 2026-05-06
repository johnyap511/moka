<?php

namespace App\OtherModel;

use Illuminate\Database\Eloquent\Model;

class UserMail extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'user_mails';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id', 'mailable_type', 'mailable_id', 'status'

        //mailable_type=> book_reminder, book_feedback
    ];
}

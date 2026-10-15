<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/** One execution of the PMS check: what was compared and how many differences were open after it. */
class PmsReconcileRun extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['from' => 'date', 'to' => 'date', 'ran_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

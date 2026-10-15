<?php

namespace App\Models;

use App\Booking;
use App\User;
use Illuminate\Database\Eloquent\Model;

/** One difference between an eZee reservation and Homemoka, kept until resolved. */
class PmsDifference extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'stay_from'     => 'date',
        'stay_to'       => 'date',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'resolved_at'   => 'datetime',
        'auto_resolved' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($q)
    {
        return $q->whereNull('resolved_at');
    }
}

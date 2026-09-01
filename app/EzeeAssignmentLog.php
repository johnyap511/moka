<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeAssignmentLog extends Model
{
    protected $fillable = [
        'ezee_booking_id', 'listing_id', 'old_listing_id', 'assigned_by', 'method', 'note',
        'resolved_at', 'resolved_by', 'resolution_note',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    /** Conflicts still waiting on a person. */
    public function scopeNeedsReview($query)
    {
        return $query->where('method', 'conflict')->whereNull('resolved_at');
    }

    public function ezeeBooking()
    {
        return $this->belongsTo(\App\OtherModel\EzeeBooking::class, 'ezee_booking_id');
    }

    public function listing()
    {
        // withoutGlobalScope: an archived property disappears from lists and
        // pickers, but a record already pointing at one must still resolve it —
        // history should stay readable after a property is handed back.
        return $this->belongsTo(Listing::class)->withoutGlobalScope('notArchived');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeAssignmentLog extends Model
{
    protected $fillable = ['ezee_booking_id', 'listing_id', 'old_listing_id', 'assigned_by', 'method', 'note'];

    public function ezeeBooking()
    {
        return $this->belongsTo(\App\OtherModel\EzeeBooking::class, 'ezee_booking_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

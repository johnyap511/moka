<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeRoomMapping extends Model
{
    protected $fillable = ['ezee_group_id', 'room_type_name', 'room_name', 'listing_id', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function ezeeGroup()
    {
        return $this->belongsTo(EzeeGroup::class);
    }
}

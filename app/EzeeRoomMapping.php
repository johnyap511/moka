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
        // withoutGlobalScope: an archived property disappears from lists and
        // pickers, but a record already pointing at one must still resolve it —
        // history should stay readable after a property is handed back.
        return $this->belongsTo(Listing::class)->withoutGlobalScope('notArchived');
    }

    public function ezeeGroup()
    {
        return $this->belongsTo(EzeeGroup::class);
    }
}

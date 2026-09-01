<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeRoom extends Model
{
    protected $fillable = [
        'ezee_group_id', 'hotel_code', 'ezee_room_id', 'ezee_unit_id',
        'room_name', 'room_type_name', 'is_blocked', 'last_seen_at',
    ];

    protected $casts = [
        'is_blocked'   => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function ezeeGroup()
    {
        return $this->belongsTo(EzeeGroup::class);
    }
}

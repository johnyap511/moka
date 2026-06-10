<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EzeeSyncLog extends Model
{
    protected $table = 'ezee_sync_logs';
    protected $guarded = [];
    protected $casts = ['details' => 'array'];

    public function ranBy()
    {
        return $this->belongsTo(\App\User::class, 'ran_by');
    }
}

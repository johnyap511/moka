<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A competitor the manager watches near one of our buildings, or our own "rate on sale" row (is_own). */
class MarketCompetitor extends Model
{
    protected $fillable = ['property', 'room_type', 'name', 'is_own', 'link', 'active', 'created_by'];

    protected $casts = ['is_own' => 'boolean', 'active' => 'boolean'];

    public function rates()
    {
        return $this->hasMany(MarketRate::class, 'competitor_id');
    }
}

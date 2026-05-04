<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingImage extends Model
{
    protected $fillable = ['listing_id', 'path', 'alt', 'sort_order'];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingImages extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'listing_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'listing_id', 'image', 'order', 'display',
    ];

    public function getUrlAttribute(): string
    {
        return asset('storage/images/' . $this->image);
    }
}

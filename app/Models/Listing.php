<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'address', 'zone',
        'price_per_night', 'bedrooms', 'bathrooms', 'max_guests',
        'rating', 'review_count', 'featured', 'status',
        'cover_image', 'amenities_list',
    ];

    protected $casts = [
        'featured'      => 'boolean',
        'amenities_list' => 'array',
        'price_per_night' => 'decimal:2',
        'rating'          => 'decimal:1',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function amenities()
    {
        return $this->hasMany(ListingAmenity::class);
    }

    public function reviews()
    {
        return $this->hasMany(ListingReview::class)->latest()->limit(10);
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->cover_image
            ? asset('storage/' . $this->cover_image)
            : asset('/new-theme23/images/property-placeholder.jpg');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'RM ' . number_format($this->price_per_night, 0);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

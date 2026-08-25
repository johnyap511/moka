<?php

namespace App;

use App\OtherModel\ListingAmenities;
use App\OtherModel\ListingZone;
use App\OtherModel\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Listing extends Model
{
    protected $table = 'listings';

    protected $fillable = [
        'user_id','ezee_hotel_code','ezee_auth_code','ezee_room_id','name','key','title', 'address', 'profit','video',
        'agent_code', 'banner', 'type', 'default_price', 'cleaning_fee',
        'tourism_tax_type','tourism_tax_amount','room_type','status','water','internet','electricity','mfsf'
    ];

    public function zoneIds()
    {
        return ListingZone::where('listing_id', $this->id)->pluck('zone_id')->toArray();
    }

    public function zones()
    {
        return $this->hasManyThrough(Zone::class, ListingZone::class, 'listing_id', 'id');
    }

    public function amenitiesIds()
    {
        return ListingAmenities::where('listing_id', $this->id)->pluck('amenities_id')->toArray();
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenities::class, ListingAmenities::class, 'listing_id', 'amenities_id')->orderBy('order', 'ASC');
    }

    public function details()
    {
        return $this->hasOne(ListingDetail::class, 'listing_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(ListingImages::class, 'listing_id')->orderBy('order');
    }

    public function reviews()
    {
        return $this->hasMany(RateReview::class, 'listing_id')->where('status', 1)->orderBy('created_at', 'desc');
    }

    public function getCoverUrlAttribute(): string
    {
        $first = $this->images->first();
        if ($first) {
            return asset('storage/images/' . $first->image);
        }
        return asset('images/placeholder.jpg');
    }

    public function getZoneAttribute(): string
    {
        return $this->zones->first()?->name ?? 'Malaysia';
    }

    public function getBedroomsAttribute(): int
    {
        return (int) ($this->room_type ?? 1);
    }

    public function getBathroomsAttribute(): int
    {
        return max(1, (int) ($this->room_type ?? 1));
    }

    public function getMaxGuestsAttribute(): int
    {
        return max(2, ((int) ($this->room_type ?? 1)) * 2);
    }

    public function getRatingAttribute(): float
    {
        return (float) $this->reviews->avg('listing_rate') ?? 0;
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews->count();
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->details?->description;
    }

    public function getSlugAttribute(): string
    {
        return $this->key ?: Str::slug($this->name ?? $this->id);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'RM ' . number_format($this->default_price, 0);
    }
}
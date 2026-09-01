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
        'tourism_tax_type','tourism_tax_amount','room_type','status','water','internet','electricity','mfsf','archived_at'
    ];

    protected $casts = ['archived_at' => 'datetime'];

    /**
     * Archived properties are excluded from every query by default.
     *
     * Applied globally rather than at each call site because a listing appears
     * in calendars, booking pickers, reports, the sitemap and the public site,
     * and archiving has to mean gone from all of them. Filtering them out one
     * query at a time would leave a property the business no longer manages
     * showing up wherever a query was missed.
     *
     * Use withArchived() or archived() to see them.
     */
    protected static function booted()
    {
        static::addGlobalScope('notArchived', function ($query) {
            $query->whereNull($query->getQuery()->from . '.archived_at');
        });
    }

    /** Properties the business still manages. The default. */
    public function scopeActive($query)
    {
        return $query;
    }

    /** Only archived properties. */
    public function scopeArchived($query)
    {
        return $query->withoutGlobalScope('notArchived')->whereNotNull('archived_at');
    }

    /** Both, for screens and actions that must reach an archived property. */
    public function scopeWithArchived($query)
    {
        return $query->withoutGlobalScope('notArchived');
    }

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
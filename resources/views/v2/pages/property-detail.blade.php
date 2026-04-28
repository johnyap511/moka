@extends('v2.partial.layout')

@section('title', ($listing->title ?? $listing->name) . ' — MOKA')
@section('meta_description', substr(strip_tags($listing->description ?? ''), 0, 155))

@php
    $listingDetails  = $listing->details ?? null;
    $reviews         = \App\RateReview::where([['listing_id', $listing->id], ['status', 1]])->get();
    $reviewCount     = count($reviews);
    $rate            = $reviewCount > 0 ? round($reviews->sum('listing_rate') / $reviewCount, 1) : null;
    $listingImages   = \App\ListingImages::where('listing_id', $listing->id)->orderBy('order', 'ASC')->get();
    $listingCategory = \App\ListingCategory::where('listing_id', $listing->id)->first();
    $isShort         = $listingCategory && $listingCategory->category_id == 1;
    $priceLabel      = $isShort ? 'night' : 'month';
@endphp

@section('content')

<div class="property-detail-hero">
    <div class="container">

        {{-- Breadcrumb --}}
        <div style="display:flex; align-items:center; gap:var(--space-2); font-size:var(--text-sm); color:var(--gray-400); margin-bottom:var(--space-5);">
            <a href="{{ url('/') }}" style="color:var(--gray-400); transition:color 0.15s;" onmouseover="this.style.color='var(--orange)'" onmouseout="this.style.color='var(--gray-400)'">Home</a>
            <span>›</span>
            <a href="{{ url('/location/search') }}" style="color:var(--gray-400); transition:color 0.15s;" onmouseover="this.style.color='var(--orange)'" onmouseout="this.style.color='var(--gray-400)'">Properties</a>
            <span>›</span>
            <span style="color:var(--gray-700);">{{ $listing->title ?? $listing->name }}</span>
        </div>

        {{-- Photo Gallery --}}
        <div class="property-gallery" style="position:relative;">
            {{-- Main photo --}}
            <div class="gallery-main">
                @if(!empty($listing->video))
                    <iframe style="width:100%; height:100%;"
                            src="https://www.youtube.com/embed/{{ $listing->video }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                @else
                    <img src="{{ asset('/images/listing/' . $listing->banner) }}"
                         alt="{{ $listing->title ?? $listing->name }}"
                         style="width:100%; height:100%; object-fit:cover;">
                @endif
            </div>

            {{-- Thumbnails --}}
            @foreach($listingImages->take(4) as $i => $img)
                <div class="gallery-thumb {{ $i === 3 && $listingImages->count() > 4 ? 'more-overlay' : '' }}"
                     data-count="+{{ $listingImages->count() - 4 }} photos">
                    <img src="{{ asset('/images/listing/' . $img->image) }}"
                         alt="Property photo {{ $i + 1 }}"
                         loading="lazy"
                         style="width:100%; height:100%; object-fit:cover;">
                </div>
            @endforeach

            <button class="show-all-photos" id="showPhotosBtn">
                📷 Show all photos
            </button>
        </div>

    </div>
</div>

{{-- Detail Content --}}
<div style="background:var(--white); padding-bottom:var(--space-20);">
    <div class="container">
        <div class="detail-layout">

            {{-- ── MAIN CONTENT ── --}}
            <div class="detail-main">

                <h1 class="detail-title">{{ $listing->title ?? $listing->name }}</h1>

                <div class="detail-meta-row">
                    @if($rate)
                        <div class="detail-rating">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            {{ $rate }}
                            <span class="reviews">&nbsp;{{ $reviewCount }} {{ $reviewCount == 1 ? 'review' : 'reviews' }}</span>
                        </div>
                    @endif
                    <div class="detail-location">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ $listing->address }}
                    </div>
                </div>

                {{-- Specs --}}
                <div class="detail-specs">
                    @if(!empty($listing->bedrooms))
                        <div class="spec-item">
                            <span class="spec-label">Bedrooms</span>
                            <span class="spec-value">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                {{ $listing->bedrooms }}
                            </span>
                        </div>
                    @endif
                    @if(!empty($listing->bathrooms))
                        <div class="spec-item">
                            <span class="spec-label">Bathrooms</span>
                            <span class="spec-value">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.683 3 4 3.683 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path d="M3 13h18"/></svg>
                                {{ $listing->bathrooms }}
                            </span>
                        </div>
                    @endif
                    @if(!empty($listing->max_guest))
                        <div class="spec-item">
                            <span class="spec-label">Max Guests</span>
                            <span class="spec-value">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                {{ $listing->max_guest }}
                            </span>
                        </div>
                    @endif
                    <div class="spec-item">
                        <span class="spec-label">Stay Type</span>
                        <span class="spec-value" style="font-size:var(--text-base);">
                            {{ $isShort ? 'Short Stay' : 'Flexible Stay' }}
                        </span>
                    </div>
                </div>

                {{-- Description --}}
                @if(!empty($listing->description))
                    <div class="detail-description">
                        {!! nl2br(e($listing->description)) !!}
                    </div>
                @endif

                {{-- Amenities --}}
                @if(!empty($listingDetails) && $listingDetails->count())
                    <h2 class="amenities-title">What this place offers</h2>
                    <div class="amenities-grid">
                        @foreach($listingDetails as $detail)
                            <div class="amenity-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>
                                {{ $detail->name ?? $detail->detail }}
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Reviews --}}
                @if($reviewCount > 0)
                    <div style="border-top:1px solid var(--gray-100); padding-top:var(--space-8); margin-top:var(--space-4);">
                        <h2 class="amenities-title">
                            @if($rate)
                                <svg width="28" height="28" viewBox="0 0 24 24" style="fill:#f59e0b; vertical-align:middle; margin-right:8px;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ $rate }} ·
                            @endif
                            {{ $reviewCount }} {{ $reviewCount == 1 ? 'Review' : 'Reviews' }}
                        </h2>

                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:var(--space-6); margin-top:var(--space-6);">
                            @foreach($reviews->take(6) as $review)
                                <div style="background:var(--gray-50); border-radius:var(--radius-md); padding:var(--space-5);">
                                    <div style="display:flex; align-items:center; gap:var(--space-3); margin-bottom:var(--space-3);">
                                        <div style="width:40px; height:40px; border-radius:50%; background:var(--teal); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:var(--text-sm);">
                                            {{ strtoupper(substr($review->name ?? 'G', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:600; color:var(--gray-800); font-size:var(--text-sm);">{{ $review->name ?? 'Guest' }}</div>
                                            <div style="font-size:var(--text-xs); color:var(--gray-400);">{{ \Carbon\Carbon::parse($review->created_at)->format('M Y') }}</div>
                                        </div>
                                    </div>
                                    <p style="font-size:var(--text-sm); color:var(--gray-600); line-height:1.65;">{{ $review->review ?? $review->comment }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            {{-- ── BOOKING WIDGET ── --}}
            <div>
                <div class="booking-widget" data-price-per-night="{{ $listing->default_price }}">

                    <div class="booking-widget-price">
                        <div class="booking-price-main">
                            RM{{ number_format($listing->default_price) }}
                            <span>/ {{ $priceLabel }}</span>
                        </div>
                        @if($rate)
                            <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-sm); color:var(--gray-500); margin-top:4px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="#f59e0b"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ $rate }} · {{ $reviewCount }} {{ $reviewCount == 1 ? 'review' : 'reviews' }}
                            </div>
                        @endif
                    </div>

                    @if($isShort)
                        {{-- Date picker for short stay --}}
                        <form action="{{ url('/booking/' . $listing->key) }}" method="GET">
                            <div class="booking-dates-grid">
                                <div class="booking-date-field">
                                    <label for="bCheckIn">Check-in</label>
                                    <input id="bCheckIn" type="date" name="check_in" class="date-field-input">
                                </div>
                                <div class="booking-date-field">
                                    <label for="bCheckOut">Check-out</label>
                                    <input id="bCheckOut" type="date" name="check_out" class="date-field-input">
                                </div>
                            </div>

                            <div class="booking-guests">
                                <label for="bGuests">Guests</label>
                                <select id="bGuests" name="guest">
                                    @for($i=1; $i<=($listing->max_guest ?? 10); $i++)
                                        <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'guest' : 'guests' }}</option>
                                    @endfor
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Reserve Now</button>

                            <p style="text-align:center; font-size:var(--text-xs); color:var(--gray-400); margin-top:var(--space-3);">You won't be charged yet</p>

                            <div class="booking-price-breakdown">
                                <div class="price-row">
                                    <span>RM{{ number_format($listing->default_price) }} × nights</span>
                                    <span class="nights-row">—</span>
                                </div>
                                <div class="price-row total">
                                    <span>Total</span>
                                    <span class="price-total-value">—</span>
                                </div>
                            </div>
                        </form>
                    @else
                        {{-- Enquiry for long stay --}}
                        <a href="https://wa.me/message/GJMYMABOT7CSG1?text={{ urlencode('Hi MOKA, I am interested in ' . ($listing->title ?? $listing->name)) }}"
                           target="_blank" rel="noopener"
                           class="btn btn-primary"
                           style="width:100%; justify-content:center; margin-bottom:var(--space-4); text-decoration:none; display:flex; padding:16px; border-radius:var(--radius-md);">
                            Enquire via WhatsApp
                        </a>
                        <p style="text-align:center; font-size:var(--text-xs); color:var(--gray-400);">Our team will respond within 1 business hour</p>
                    @endif

                    <div style="display:flex; align-items:center; justify-content:center; gap:var(--space-2); margin-top:var(--space-5); padding-top:var(--space-4); border-top:1px solid var(--gray-100);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span style="font-size:var(--text-xs); color:var(--gray-400);">Verified MOKA property</span>
                    </div>
                </div>

                {{-- Contact Box --}}
                <div style="margin-top:var(--space-5); background:var(--teal-light); border-radius:var(--radius-lg); padding:var(--space-5); text-align:center;">
                    <p style="font-size:var(--text-sm); font-weight:600; color:var(--teal); margin-bottom:var(--space-3);">Have questions?</p>
                    <a href="tel:60367892288"
                       style="display:flex; align-items:center; justify-content:center; gap:var(--space-2); color:var(--teal); font-weight:600; font-size:var(--text-sm); text-decoration:none; margin-bottom:var(--space-2);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6.06 6.06l1.86-1.86a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        +603 6789 2288
                    </a>
                    <a href="mailto:hello@homemoka.com"
                       style="display:flex; align-items:center; justify-content:center; gap:var(--space-2); color:var(--teal); font-weight:600; font-size:var(--text-sm); text-decoration:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        hello@homemoka.com
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

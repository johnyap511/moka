@extends('v2.partial.layout')

@section('title', $listing->title . ' — MOKA')
@section('meta_description', substr(strip_tags($listing->description ?? ''), 0, 155))

@section('head')
<meta property="og:title"       content="{{ $listing->title }} — MOKA">
<meta property="og:description" content="{{ substr(strip_tags($listing->description ?? ''), 0, 155) }}">
<meta property="og:image"       content="{{ $listing->cover_url }}">
@endsection

@section('content')

{{-- Gallery --}}
<div class="detail-hero">
    <div class="container">
        <div class="gallery-grid" style="margin-top:calc(var(--header-h) + var(--space-6));">

            @php $images = $listing->images; @endphp

            <div class="gallery-main">
                <img src="{{ $images->first()?->url ?? $listing->cover_url }}"
                     alt="{{ $listing->title }}"
                     loading="eager">
            </div>

            @foreach($images->skip(1)->take(4) as $img)
                <div class="gallery-thumb">
                    <img src="{{ $img->url }}" alt="{{ $img->alt ?? $listing->title }}" loading="lazy">
                </div>
            @endforeach

            @if($images->count() > 5)
                <button class="show-all-photos" aria-label="Show all photos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Show all {{ $images->count() }} photos
                </button>
            @endif
        </div>
    </div>
</div>

{{-- Gallery Overlay --}}
<div class="gallery-overlay" id="galleryOverlay" aria-hidden="true" role="dialog">
    <button class="gallery-close" aria-label="Close gallery">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <div class="gallery-overlay-imgs">
        @foreach($listing->images as $img)
            <img src="{{ $img->url }}" alt="{{ $img->alt ?? $listing->title }}" loading="lazy">
        @endforeach
    </div>
</div>

{{-- Detail Body --}}
<section class="detail-body">
    <div class="container">
        <div class="detail-layout">

            {{-- Main Content --}}
            <div>
                <span class="section-label text-orange" data-reveal>{{ $listing->zone }}</span>

                <h1 class="detail-title" data-reveal>{{ $listing->title }}</h1>

                <div class="detail-address" data-reveal>
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $listing->address }}
                </div>

                <div class="detail-stats" data-reveal>
                    <div class="detail-stat">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        {{ $listing->bedrooms }} Bedrooms
                    </div>
                    <div class="detail-stat">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 12h16M4 12a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2M4 12v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/></svg>
                        {{ $listing->bathrooms }} Bathrooms
                    </div>
                    <div class="detail-stat">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Up to {{ $listing->max_guests }} Guests
                    </div>
                    @if($listing->rating > 0)
                    <div class="detail-stat">
                        <svg viewBox="0 0 24 24" fill="#fbbf24" stroke="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        {{ number_format($listing->rating, 1) }} ({{ $listing->review_count }} reviews)
                    </div>
                    @endif
                </div>

                {{-- Description --}}
                <div data-reveal>
                    <h2 class="display-sm text-teal" style="margin-bottom:var(--space-4);">About this property</h2>
                    <div class="detail-desc" data-read-more>
                        <div>{!! nl2br(e(Str::limit($listing->description ?? '', 400))) !!}</div>
                        @if(strlen($listing->description ?? '') > 400)
                            <div data-read-more-full>{!! nl2br(e(substr($listing->description ?? '', 400))) !!}</div>
                            <button class="read-more-btn">Read more ↓</button>
                        @endif
                    </div>
                </div>

                {{-- Amenities --}}
                @if($listing->amenities->isNotEmpty())
                <div style="margin-top:var(--space-10);" data-reveal>
                    <h2 class="display-sm text-teal" style="margin-bottom:var(--space-6);">Amenities</h2>
                    <div class="amenities-grid">
                        @foreach($listing->amenities as $amenity)
                            <div class="amenity-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $amenity->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Reviews --}}
                @if($listing->reviews->isNotEmpty())
                <div style="margin-top:var(--space-12);" data-reveal>
                    <h2 class="display-sm text-teal" style="margin-bottom:var(--space-6);">
                        Guest Reviews
                        <span style="font-size:var(--text-lg); font-family:var(--font-body); font-weight:400; color:var(--gray-400); margin-left:var(--space-2);">({{ $listing->review_count }})</span>
                    </h2>
                    <div class="reviews-grid">
                        @foreach($listing->reviews as $review)
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-avatar">
                                        @if($review->reviewer_avatar)
                                            <img src="{{ asset('storage/' . $review->reviewer_avatar) }}" alt="{{ $review->reviewer_name }}">
                                        @else
                                            {{ strtoupper(substr($review->reviewer_name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="review-name">{{ $review->reviewer_name }}</div>
                                        @if($review->stay_date)
                                            <div class="review-date">{{ $review->stay_date->format('M Y') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="review-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg viewBox="0 0 24 24" style="{{ $i <= $review->rating ? 'fill:#fbbf24' : 'fill:#e5e7eb' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @endfor
                                </div>
                                <p class="review-text">{{ $review->comment }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Booking Widget --}}
            <div>
                <div class="booking-widget" data-price-per-night="{{ $listing->price_per_night }}">
                    <div class="booking-price">
                        <span class="booking-price-amount">{{ $listing->formatted_price }}</span>
                        <span class="booking-price-unit">/ night</span>
                        @if($listing->rating > 0)
                            <span class="booking-price-rating">
                                <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ number_format($listing->rating, 1) }}
                            </span>
                        @endif
                    </div>

                    <form method="GET" action="https://staymoka.com/booking" target="_blank">
                        <input type="hidden" name="listing" value="{{ $listing->slug }}">

                        <div class="booking-dates">
                            <div class="date-field">
                                <div class="date-field-label">Check-in</div>
                                <input type="date" name="check_in" class="date-field-input" value="{{ request('check_in') }}">
                            </div>
                            <div class="date-field">
                                <div class="date-field-label">Check-out</div>
                                <input type="date" name="check_out" class="date-field-input" value="{{ request('check_out') }}">
                            </div>
                        </div>

                        <div class="booking-guests">
                            <div class="booking-guests-label">Guests</div>
                            <select name="guests">
                                @for($i = 1; $i <= $listing->max_guests; $i++)
                                    <option value="{{ $i }}" {{ request('guest_no', 1) == $i ? 'selected' : '' }}>{{ $i }} guest{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; font-size:var(--text-base);">
                            Reserve Now
                        </button>
                    </form>

                    <div class="booking-price-breakdown">
                        <div class="price-row">
                            <span>{{ $listing->formatted_price }} × nights</span>
                            <span class="nights-row">—</span>
                        </div>
                        <div class="price-row total">
                            <span>Total</span>
                            <span class="price-total-value">—</span>
                        </div>
                    </div>

                    <p style="text-align:center; font-size:var(--text-xs); color:var(--gray-400); margin-top:var(--space-4);">No charges yet — confirm on the next page</p>
                </div>

                {{-- WhatsApp CTA --}}
                <div style="margin-top:var(--space-4);">
                    <a href="https://wa.me/message/GJMYMABOT7CSG1?text=Hi%2C+I'm+interested+in+{{ urlencode($listing->title) }}"
                       target="_blank"
                       rel="noopener"
                       class="whatsapp-btn">
                        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.119.553 4.107 1.522 5.834L0 24l6.335-1.502A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.667-.493-5.209-1.355l-.374-.222-3.755.89.949-3.658-.244-.389A9.939 9.939 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        Enquire via WhatsApp
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Similar Properties --}}
@if($similar->isNotEmpty())
<section class="similar-section">
    <div class="container">
        <h2 class="display-md text-teal" data-reveal>More properties in {{ $listing->zone }}</h2>
        <div class="similar-grid">
            @foreach($similar as $i => $prop)
                <article class="property-card" data-reveal data-delay="{{ $i * 100 }}">
                    <a href="{{ url('/listing/' . $prop->slug) }}" class="property-card-img">
                        <img src="{{ $prop->cover_url }}" alt="{{ $prop->title }}" loading="lazy">
                        @if($prop->rating > 0)
                            <span class="property-card-rating">
                                <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ number_format($prop->rating, 1) }}
                            </span>
                        @endif
                    </a>
                    <div class="property-card-body">
                        <span class="property-card-zone">{{ $prop->zone }}</span>
                        <h3 class="property-card-title">
                            <a href="{{ url('/listing/' . $prop->slug) }}">{{ $prop->title }}</a>
                        </h3>
                        <div class="property-card-footer">
                            <div class="property-card-price">{{ $prop->formatted_price }}<span> / night</span></div>
                            <a href="{{ url('/listing/' . $prop->slug) }}" class="property-card-cta">View →</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

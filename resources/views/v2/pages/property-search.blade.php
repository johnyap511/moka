@extends('v2.partial.layout')

@section('title', 'Properties in ' . ($selectedZone->name ?? 'Malaysia') . ' — MOKA')
@section('meta_description', 'Browse short-stay properties in ' . ($selectedZone->name ?? 'Malaysia') . '. Book directly with MOKA.')

@section('content')

{{-- Search Hero --}}
<section class="search-hero">
    <div class="container">
        <h1 class="search-hero-title" data-reveal>
            Stay in {{ $selectedZone->name ?? 'Malaysia' }}
        </h1>

        <div class="search-bar" data-reveal>
            <form method="GET" action="{{ url('/location/search') }}"
                  style="display:contents;">

                <div class="search-field">
                    <label class="search-field-label">Location</label>
                    <input class="search-field-input"
                           type="text"
                           name="location"
                           placeholder="Where to?"
                           value="{{ $selectedZone->name ?? '' }}">
                </div>

                <div class="search-divider"></div>

                <div class="search-field">
                    <label class="search-field-label">Check-in</label>
                    <input class="search-field-input date-field-input"
                           type="date"
                           name="check_in"
                           value="{{ $checkIn ?? '' }}">
                </div>

                <div class="search-divider"></div>

                <div class="search-field">
                    <label class="search-field-label">Check-out</label>
                    <input class="search-field-input date-field-input"
                           type="date"
                           name="check_out"
                           value="{{ $checkOut ?? '' }}">
                </div>

                <div class="search-divider"></div>

                <div class="search-field">
                    <label class="search-field-label">Guests</label>
                    <select class="search-field-input" name="guest" style="border:none; background:transparent; outline:none; font-family:inherit; font-size:var(--text-sm); color:var(--gray-800); cursor:pointer;">
                        @for($i=1; $i<10; $i++)
                            <option value="{{ $i }}" @if(($guestNo ?? 2) == $i) selected @endif>
                                {{ $i }} {{ $i == 1 ? 'Guest' : 'Guests' }}
                            </option>
                        @endfor
                    </select>
                </div>

                <button type="submit" class="search-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    Search
                </button>

            </form>
        </div>
    </div>
</section>

{{-- Results --}}
<section class="search-results-layout">
    <div class="container">

        <div class="results-header">
            <p class="results-count">
                <strong>{{ $listings->count() }}</strong>
                {{ $listings->count() == 1 ? 'property' : 'properties' }}
                in {{ $selectedZone->name ?? 'Malaysia' }}
            </p>
            <select class="sort-select" onchange="window.location.href=this.value">
                <option value="#">Sort: Recommended</option>
                <option value="?sort=price_asc">Price: Low to High</option>
                <option value="?sort=price_desc">Price: High to Low</option>
                <option value="?sort=rating">Top Rated</option>
            </select>
        </div>

        <div class="listings-grid">
            @forelse($listings as $listing)
                @php
                    $banner = asset('/new-theme/images/taylor-simpson-EWcocEYrkAk-unsplash.jpg');
                    if (!empty($listing->banner)) {
                        $banner = asset('/images/listing/' . $listing->banner);
                    }
                    $priceStart = \App\ListingPrice::where([['listing_id', $listing->id], ['date', '>=', date('Y-m-d')]])->min('price');
                    if (empty($priceStart) || $priceStart > $listing->default_price) {
                        $priceStart = $listing->default_price;
                    }
                    $reviews = \App\RateReview::where([['listing_id', $listing->id], ['status', 1]])->get();
                    $reviewCount = count($reviews);
                    $rate = $reviewCount > 0 ? round($reviews->sum('listing_rate') / $reviewCount, 1) : null;
                    $category = \App\ListingCategory::where('listing_id', $listing->id)->first();
                    $isShort = $category && $category->category_id == 1;
                @endphp

                <article class="listing-card" data-reveal>
                    <a href="{{ url('/listing/' . $listing->key) }}">
                        <div class="listing-card-img">
                            <img src="{{ $banner }}"
                                 alt="{{ $listing->title ?? $listing->name }}"
                                 loading="lazy">

                            <span class="listing-card-badge {{ $isShort ? 'short' : '' }}">
                                {{ $isShort ? 'Short Stay' : 'Flexible Stay' }}
                            </span>

                            <button type="button" class="listing-card-wishlist" aria-label="Save to wishlist">
                                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
                        </div>
                    </a>

                    <div class="listing-card-body">
                        <div class="listing-location">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ substr($listing->address, 0, 35) }}{{ strlen($listing->address) > 35 ? '…' : '' }}
                        </div>

                        <a href="{{ url('/listing/' . $listing->key) }}">
                            <h2 class="listing-card-name">{{ $listing->title ?? $listing->name }}</h2>
                        </a>

                        <div class="listing-card-meta">
                            @if(!empty($listing->bedrooms))
                                <span class="listing-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                    {{ $listing->bedrooms }} bed
                                </span>
                            @endif
                            @if(!empty($listing->bathrooms))
                                <span class="listing-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.683 3 4 3.683 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path d="M3 13h18"/></svg>
                                    {{ $listing->bathrooms }} bath
                                </span>
                            @endif
                            @if(!empty($listing->max_guest))
                                <span class="listing-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    {{ $listing->max_guest }} guests
                                </span>
                            @endif
                        </div>

                        <div class="listing-card-footer">
                            <div class="listing-price">
                                RM{{ number_format($priceStart) }}
                                <span>/{{ $isShort ? 'night' : 'month' }}</span>
                            </div>

                            @if($rate)
                                <div class="listing-rating">
                                    <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ $rate }}
                                    <span style="font-weight:400; color:var(--gray-400);">({{ $reviewCount }})</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>

            @empty
                <div style="grid-column:1/-1; text-align:center; padding:var(--space-20) 0;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5" style="margin:0 auto var(--space-4);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <h3 class="display-sm text-teal" style="margin-bottom:var(--space-3);">No properties found</h3>
                    <p style="color:var(--gray-400);">Try adjusting your search — different dates or location.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if(method_exists($listings, 'hasPages') && $listings->hasPages())
            <div class="pagination">
                {{ $listings->links() }}
            </div>
        @endif

    </div>
</section>

@endsection

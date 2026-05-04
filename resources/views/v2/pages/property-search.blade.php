@extends('v2.partial.layout')

@section('title', 'Properties' . ($selectedZone ? ' in ' . $selectedZone : '') . ' — MOKA')
@section('meta_description', 'Browse premium short-stay properties managed by MOKA across Malaysia. Book directly for the best rates.')

@section('content')

{{-- Search Hero --}}
<section class="search-hero">
    <div class="container">
        <h1 data-reveal>
            @if($selectedZone)
                Properties in {{ $selectedZone }}
            @else
                Find Your Perfect Stay
            @endif
        </h1>

        <form method="GET" action="{{ url('/location/search') }}" data-reveal>
            <div class="search-bar">
                <div class="search-field">
                    <span class="search-field-label">Location</span>
                    <input type="text" name="zone" placeholder="Where are you going?" value="{{ $selectedZone }}">
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <span class="search-field-label">Check-in</span>
                    <input type="date" name="check_in" value="{{ $checkIn }}" class="date-field-input">
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <span class="search-field-label">Check-out</span>
                    <input type="date" name="check_out" value="{{ $checkOut }}" class="date-field-input">
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <span class="search-field-label">Guests</span>
                    <select name="guest_no">
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ $guestNo == $i ? 'selected' : '' }}>{{ $i }} Guest{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="search-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Search
                </button>
            </div>
        </form>
    </div>
</section>

{{-- Filter Bar --}}
<div class="filter-bar">
    <div class="container">
        <div class="filter-bar-inner">
            <span class="filter-count">
                {{ $listings->total() }} {{ Str::plural('property', $listings->total()) }}
                @if($selectedZone) in {{ $selectedZone }} @endif
            </span>

            <a href="{{ request()->fullUrlWithQuery(['sort' => 'recommended']) }}"
               class="filter-chip {{ (!request('sort') || request('sort') === 'recommended') ? 'active' : '' }}">
                Recommended
            </a>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}"
               class="filter-chip {{ request('sort') === 'price_asc' ? 'active' : '' }}">
                Price: Low to High
            </a>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}"
               class="filter-chip {{ request('sort') === 'price_desc' ? 'active' : '' }}">
                Price: High to Low
            </a>
            <a href="{{ request()->fullUrlWithQuery(['sort' => 'rating']) }}"
               class="filter-chip {{ request('sort') === 'rating' ? 'active' : '' }}">
                Top Rated
            </a>
        </div>
    </div>
</div>

{{-- Results --}}
<section class="results-section">
    <div class="container">

        @if($listings->isEmpty())
            <div class="results-grid">
                <div class="results-empty" data-reveal>
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <h3>No properties found</h3>
                    <p>Try a different location or adjust your search.</p>
                    <a href="{{ url('/location/search') }}" class="btn btn-teal">View All Properties</a>
                </div>
            </div>
        @else
            <div class="results-grid">
                @foreach($listings as $i => $property)
                    <article class="property-card" data-reveal data-delay="{{ ($i % 3) * 100 }}">
                        <a href="{{ url('/listing/' . $property->slug) }}" class="property-card-img">
                            <img src="{{ $property->cover_url }}"
                                 alt="{{ $property->title }}"
                                 loading="{{ $i < 6 ? 'eager' : 'lazy' }}">
                            @if($property->featured)
                                <span class="property-card-badge">Featured</span>
                            @endif
                            @if($property->rating > 0)
                                <span class="property-card-rating">
                                    <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ number_format($property->rating, 1) }}
                                </span>
                            @endif
                        </a>
                        <div class="property-card-body">
                            <span class="property-card-zone">{{ $property->zone }}</span>
                            <h2 class="property-card-title">
                                <a href="{{ url('/listing/' . $property->slug) }}">{{ $property->title }}</a>
                            </h2>
                            <div class="property-card-meta">
                                <span class="property-card-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                    {{ $property->bedrooms }} Bed
                                </span>
                                <span class="property-card-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 12h16M4 12a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2M4 12v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/></svg>
                                    {{ $property->bathrooms }} Bath
                                </span>
                                <span class="property-card-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    Up to {{ $property->max_guests }}
                                </span>
                            </div>
                            <div class="property-card-footer">
                                <div class="property-card-price">
                                    {{ $property->formatted_price }}<span> / night</span>
                                </div>
                                <a href="{{ url('/listing/' . $property->slug) }}" class="property-card-cta">View →</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($listings->hasPages())
                <div class="pagination-wrap" data-reveal>
                    {{ $listings->links() }}
                </div>
            @endif
        @endif

    </div>
</section>

@endsection

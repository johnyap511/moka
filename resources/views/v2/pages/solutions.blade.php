{{--
    Solutions index. Route: /solutions → Auth\WebController@solutions.
    Homepage template, blog styling.
--}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Property Management Solutions in Malaysia: Airbnb, Short-Stay, Monthly Rental, Corporate Housing | MOKA')
@section('seo_description', 'MOKA for homeowners, investors, developers and agents: Airbnb and short-term rental management, monthly and weekly rental, corporate housing and renovation across Malaysia.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/solutions23.css') }}">
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero sol-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Solutions</div>
            <h1>One team for <em>every way</em> a property can earn</h1>
            <p class="blog-hero__meta">Nightly, weekly, monthly or corporate. Owned by a family, an investor, a developer, or introduced by an agent. Pick the page that fits you.</p>
        </div>
    </div>

    <div class="blog-list">
        <div class="blog-list__inner">
            <div class="sol-grid">
                @foreach($pages as $p)
                <a href="/solutions/{{ $p['slug'] }}" class="sol-tile">
                    <div class="sol-tile__eyebrow">{{ $p['eyebrow'] }}</div>
                    <h2>{!! $p['h1'] !!}</h2>
                    <p>{{ \Illuminate\Support\Str::limit($p['sub'], 150) }}</p>
                    <span>Read more →</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection

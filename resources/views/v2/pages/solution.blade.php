{{--
    Solution landing page. Route: /solutions/{slug} → Auth\WebController@solution.
    Content from config/solutions.php. Built on the homepage template
    (auth.newTheme.layout) with the blog's styling, so it is one site.
--}}
@extends('auth.newTheme.layout')
@section('seo_title', $page['title'])
@section('seo_description', $page['description'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/solutions23.css') }}">
@endpush

@push('schema')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'Service', 'name' => strip_tags($page['h1']), 'serviceType' => $page['label'], 'provider' => ['@id' => url('/') . '/#organization'], 'areaServed' => ['Kuala Lumpur', 'Selangor', 'Penang', 'Johor Bahru', 'Kota Kinabalu'], 'description' => $page['description'], 'url' => url('/solutions/' . $page['slug'])],
        ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $page['faqs'] ?? [])],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'Solutions', 'item' => url('/solutions')], ['@type' => 'ListItem', 'position' => 3, 'name' => $page['label'], 'item' => url('/solutions/' . $page['slug'])]]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@php
    $groups = [
        'For you'      => ['for-homeowners', 'for-property-investors', 'for-property-developers', 'for-property-agents'],
        'By stay type' => ['airbnb-management-malaysia', 'short-term-rental-management', 'monthly-rental', 'corporate-housing'],
        'By city'      => ['airbnb-management-kuala-lumpur', 'airbnb-management-selangor', 'airbnb-management-penang', 'airbnb-management-johor-bahru', 'airbnb-management-kota-kinabalu'],
    ];
    $bySlug = collect($all)->keyBy('slug');
    $group  = collect($groups)->filter(fn ($slugs) => in_array($page['slug'], $slugs))->keys()->first();
    $siblings = $group ? $groups[$group] : ['for-homeowners', 'for-property-investors', 'for-property-developers', 'for-property-agents'];
@endphp

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero sol-hero">
        <div class="sol-hero__grid sol-hero__grid--single">
            <div class="blog-hero__inner">
                <div class="blog-hero__eyebrow">{{ $page['eyebrow'] }}</div>
                <h1>{!! $page['h1'] !!}</h1>
                <p class="blog-hero__meta">{{ $page['sub'] }}</p>
                <div class="sol-hero__actions">
                    <a href="/get/estimate" target="_blank" rel="noopener" class="primary-btn">Get a free estimate</a>
                    <a href="/contact" class="white-btn">Talk to us</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sol-body">
        <div class="sol-body__inner">
            <div class="sol-main">
                @foreach($page['sections'] as $i => $s)
                    @php $hasList = !empty($s['bullets']); @endphp
                    <div class="sol-block {{ $hasList ? 'sol-block--list' : 'sol-block--note' }}">
                        <div class="sol-block__text">
                            <div class="sol-block__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                            <h2>{{ $s['h2'] }}</h2>
                            @if(!empty($s['p']))<p>{{ $s['p'] }}</p>@endif
                            @if($hasList)<ul class="sol-check">@foreach($s['bullets'] as $b)<li>{{ $b }}</li>@endforeach</ul>@endif
                        </div>
                    </div>
                @endforeach

                @if(!empty($page['faqs']))
                <div class="sol-faq">
                    <h2>Questions owners ask</h2>
                    @foreach($page['faqs'] as $f)
                    <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>
                    @endforeach
                </div>
                @endif
            </div>

            <aside class="sol-aside">
                <div class="sol-card sol-card--teal">
                    <h3>See what your unit could earn</h3>
                    <p>A free estimate based on real bookings in comparable units. No commitment, no upfront fee.</p>
                    <a href="/get/estimate" target="_blank" rel="noopener" class="blog-cta__btn">Get a free estimate</a>
                </div>
                <div class="sol-card">
                    <h3>{{ $group ?: 'MOKA for' }}</h3>
                    <nav class="sol-nav" aria-label="Related solutions">
                        @foreach($siblings as $slug)
                            @if($bySlug->has($slug))<a href="/solutions/{{ $slug }}" class="{{ $slug === $page['slug'] ? 'cur' : '' }}">{{ $bySlug[$slug]['label'] }}</a>@endif
                        @endforeach
                        <a href="/solutions" class="sol-nav__all">All solutions and locations</a>
                    </nav>
                </div>
            </aside>
        </div>
    </div>

    <div class="sol-cta-wrap">
        <div class="blog-cta">
            <h2>Ready to earn more from your property?</h2>
            <p>Superhost and Preferred Host, rated 4.9 out of 5 by guests. Commission-only, no upfront cost.</p>
            <a href="/get/estimate" target="_blank" rel="noopener" class="blog-cta__btn">Get a free estimate</a>
        </div>
    </div>
@endsection

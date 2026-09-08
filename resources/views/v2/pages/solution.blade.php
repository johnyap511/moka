{{--
    MOKA v2 — Solution landing page
    Route: /solutions/{slug} → Auth\WebController@solution → v2.pages.solution
    Content comes from config/solutions.php; this file is layout only. Built
    from the theme's own pieces (about-hero, stats-bar, section, display-*,
    body-lg, btn) and its colour tokens, so it follows the brand and dark mode.
--}}
@extends('v2.partial.layout')
@section('title', $page['title'])
@section('meta_description', $page['description'])
@php $headerTransparent = true; @endphp

@section('head')
<style>
.sol-hero h1 em{font-style:normal;color:var(--orange)}
.sol-hero .section-label{color:var(--orange)}
.sol-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:var(--space-16);align-items:start}
.sol-block{padding:var(--space-8) 0;border-bottom:1px solid var(--gray-200)}
.sol-block:first-child{padding-top:0}
.sol-block:last-child{border-bottom:0}
.sol-block h2{font-family:var(--font-display);font-size:clamp(1.5rem,2.4vw,1.9rem);font-weight:700;color:var(--teal);margin:0 0 var(--space-3);line-height:1.2}
.sol-block p{color:var(--gray-600);margin:0 0 var(--space-3)}
.sol-check{list-style:none;margin:var(--space-3) 0 0;padding:0;display:grid;gap:var(--space-3)}
.sol-check li{position:relative;padding-left:2rem;color:var(--gray-700);line-height:1.55}
.sol-check li::before{content:"";position:absolute;left:0;top:.2rem;width:1.15rem;height:1.15rem;border-radius:50%;background:var(--orange-light)}
.sol-check li::after{content:"";position:absolute;left:.35rem;top:.42rem;width:.42rem;height:.7rem;border:solid var(--orange);border-width:0 2px 2px 0;transform:rotate(45deg)}
.sol-aside{position:sticky;top:calc(var(--header-h) + var(--space-4));display:grid;gap:var(--space-4)}
.sol-card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:var(--space-6);box-shadow:0 10px 30px rgba(0,0,0,.05)}
.sol-card.accent{background:var(--gradient-cta);color:#fff;border:0}
.sol-card.accent h3{color:#fff}
.sol-card.accent p{color:rgba(255,255,255,.78)}
.sol-card h3{font-family:var(--font-display);font-size:1.15rem;color:var(--teal);margin:0 0 var(--space-2)}
.sol-card p{font-size:var(--text-sm);color:var(--gray-600);line-height:1.6;margin:0 0 var(--space-4)}
.sol-card .btn{width:100%;justify-content:center}
.sol-nav{display:grid}
.sol-nav a{font-size:var(--text-sm);font-weight:600;color:var(--teal);text-decoration:none;padding:.55rem 0;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center}
.sol-nav a::after{content:"→";color:var(--gray-400);font-weight:400}
.sol-nav a:last-child{border-bottom:0}
.sol-nav a.cur{color:var(--orange);pointer-events:none}
.sol-nav a.cur::after{content:"•";color:var(--orange)}
.sol-faq details{border:1px solid var(--gray-200);border-radius:var(--radius-md);background:var(--white);padding:var(--space-4) var(--space-5);margin-bottom:var(--space-3)}
.sol-faq summary{cursor:pointer;font-weight:600;color:var(--gray-900);list-style:none;display:flex;justify-content:space-between;gap:var(--space-4)}
.sol-faq summary::-webkit-details-marker{display:none}
.sol-faq summary::after{content:"+";color:var(--orange);font-weight:700;font-size:1.2rem;line-height:1}
.sol-faq details[open] summary::after{content:"–"}
.sol-faq details p{margin:var(--space-3) 0 0;color:var(--gray-600);line-height:1.65}
.sol-cta{background:var(--gradient-cta);color:#fff;border-radius:var(--radius-xl);padding:var(--space-12) var(--space-8);text-align:center}
.sol-cta h2{font-family:var(--font-display);font-size:clamp(1.8rem,3vw,2.4rem);color:#fff;margin:0 0 var(--space-3)}
.sol-cta p{color:rgba(255,255,255,.78);max-width:560px;margin:0 auto var(--space-6)}
@media (max-width:960px){.sol-layout{grid-template-columns:1fr}.sol-aside{position:static}}
</style>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'Service', 'name' => strip_tags($page['h1']), 'serviceType' => $page['label'], 'provider' => ['@id' => url('/') . '/#organization'], 'areaServed' => ['Kuala Lumpur', 'Selangor', 'Penang', 'Johor Bahru', 'Kota Kinabalu'], 'description' => $page['description'], 'url' => url('/solutions/' . $page['slug'])],
        ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $page['faqs'] ?? [])],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'Solutions', 'item' => url('/solutions')], ['@type' => 'ListItem', 'position' => 3, 'name' => $page['label'], 'item' => url('/solutions/' . $page['slug'])]]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')

{{-- Hero: the About page's hero, teal with display type --}}
<section class="about-hero sol-hero">
    <div class="container">
        <div class="about-hero-content">
        <span class="section-label text-orange">{{ $page['eyebrow'] }}</span>
        <h1 class="display-hero about-hero-headline">{!! $page['h1'] !!}</h1>
        <p class="about-hero-sub">{{ $page['sub'] }}</p>
        <div class="about-hero-actions">
            <a href="{{ url('/get/estimate') }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">Get a free estimate</a>
            <a href="{{ url('/contact') }}" class="btn btn-outline btn-lg">Talk to us</a>
        </div>
        </div>
    </div>
</section>

{{-- Proof strip, the same figures as the About page --}}
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            @foreach([['100,000+', 'Trips Hosted'], ['70%', 'Avg. Occupancy'], ['RM10M+', 'Revenue for Hosts'], ['4.9★', 'Guest Rating'], ['Superhost', 'Status Awarded']] as $s)
                <div class="stat-item"><span class="stat-number">{{ $s[0] }}</span><span class="stat-label">{{ $s[1] }}</span></div>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="sol-layout">
            <div>
                @foreach($page['sections'] as $s)
                <div class="sol-block">
                    <h2>{{ $s['h2'] }}</h2>
                    @if(!empty($s['p']))<p class="body-lg">{{ $s['p'] }}</p>@endif
                    @if(!empty($s['bullets']))<ul class="sol-check">@foreach($s['bullets'] as $b)<li>{{ $b }}</li>@endforeach</ul>@endif
                </div>
                @endforeach

                @if(!empty($page['faqs']))
                <div class="sol-block sol-faq">
                    <h2>Questions owners ask</h2>
                    @foreach($page['faqs'] as $f)
                    <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>
                    @endforeach
                </div>
                @endif
            </div>

            <aside class="sol-aside">
                <div class="sol-card accent">
                    <h3>See what your unit could earn</h3>
                    <p>A free estimate based on real bookings in comparable units. No commitment, no upfront fee.</p>
                    <a href="{{ url('/get/estimate') }}" target="_blank" rel="noopener" class="btn btn-primary">Get a free estimate</a>
                </div>
                <div class="sol-card">
                    <h3>MOKA for</h3>
                    <nav class="sol-nav" aria-label="Solutions">
                        @foreach($all as $p)
                            <a href="{{ url('/solutions/' . $p['slug']) }}" class="{{ $p['slug'] === $page['slug'] ? 'cur' : '' }}">{{ $p['label'] }}</a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="sol-cta">
            <h2>Ready to earn more from your property?</h2>
            <p>Superhost and Preferred Host, rated 4.9 out of 5 by guests. Commission-only, no upfront cost.</p>
            <a href="{{ url('/get/estimate') }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">Get a free estimate</a>
        </div>
    </div>
</section>
@endsection

{{--
    MOKA v2 — Solution landing page
    Route: /solutions/{slug} → Auth\WebController@solution → v2.pages.solution
    Content comes from config/solutions.php; this file is layout only.
--}}
@extends('v2.partial.layout')
@section('title', $page['title'])
@section('meta_description', $page['description'])
@php $headerTransparent = false; @endphp

@section('head')
<style>
.sol-hero{padding:72px 0 44px;background:linear-gradient(180deg,#fff7ed 0%,#fff 100%)}
.sol-hero .section-label{color:var(--orange)}
.sol-hero h1{font-size:clamp(30px,4.2vw,50px);line-height:1.1;letter-spacing:-.02em;margin:10px 0 16px;max-width:820px}
.sol-hero h1 em{font-style:normal;color:var(--orange)}
.sol-hero .sub{font-size:18px;line-height:1.6;color:#4b5563;max-width:720px}
.sol-hero .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
.sol-body{padding:20px 0 40px}
.sol-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:40px;align-items:start}
.sol-section{padding:26px 0;border-bottom:1px solid #eef0f3}
.sol-section:last-child{border-bottom:none}
.sol-section h2{font-size:22px;letter-spacing:-.01em;margin:0 0 10px}
.sol-section p{font-size:16px;line-height:1.7;color:#374151;margin:0 0 10px}
.sol-section ul{margin:8px 0 0;padding:0;list-style:none;display:grid;gap:8px}
.sol-section li{position:relative;padding-left:26px;font-size:15.5px;line-height:1.55;color:#1f2937}
.sol-section li::before{content:"";position:absolute;left:0;top:7px;width:14px;height:14px;border-radius:50%;background:#0a8a72;box-shadow:inset 0 0 0 4px #e0f5f1}
.sol-aside{position:sticky;top:90px;display:grid;gap:14px}
.sol-card{background:#fff;border:1px solid #eef0f3;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.sol-card h3{font-size:15px;margin:0 0 8px}
.sol-card p{font-size:13.5px;color:#4b5563;line-height:1.55;margin:0 0 12px}
.sol-card .btn{width:100%;justify-content:center}
.sol-links{display:grid;gap:6px}
.sol-links a{font-size:13.5px;color:#0a8a72;font-weight:600;text-decoration:none;padding:6px 0;border-bottom:1px solid #f3f4f6}
.sol-links a.cur{color:#111827;pointer-events:none}
.sol-faq{margin-top:10px}
.sol-faq details{border:1px solid #eef0f3;border-radius:12px;padding:12px 16px;margin-bottom:8px;background:#fff}
.sol-faq summary{cursor:pointer;font-weight:600;font-size:15px;list-style:none}
.sol-faq summary::-webkit-details-marker{display:none}
.sol-faq p{margin:8px 0 0;color:#374151;font-size:14.5px;line-height:1.6}
.sol-cta{background:#0a8a72;color:#fff;border-radius:20px;padding:36px 28px;text-align:center;margin:30px 0 50px}
.sol-cta h2{font-size:26px;margin:0 0 8px;color:#fff}
.sol-cta p{color:rgba(255,255,255,.9);margin:0 0 18px}
@media (max-width:900px){.sol-grid{grid-template-columns:1fr}.sol-aside{position:static}.sol-hero{padding:48px 0 32px}}
</style>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'Service', 'name' => strip_tags(str_replace('<em>', '', $page['h1'])), 'serviceType' => $page['label'], 'provider' => ['@id' => url('/') . '/#organization'], 'areaServed' => ['Kuala Lumpur', 'Selangor', 'Penang', 'Johor Bahru', 'Kota Kinabalu'], 'description' => $page['description'], 'url' => url('/solutions/' . $page['slug'])],
        ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $page['faqs'] ?? [])],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'Solutions', 'item' => url('/solutions')], ['@type' => 'ListItem', 'position' => 3, 'name' => $page['label'], 'item' => url('/solutions/' . $page['slug'])]]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
<section class="sol-hero">
    <div class="container">
        <span class="section-label">{{ $page['eyebrow'] }}</span>
        <h1>{!! $page['h1'] !!}</h1>
        <p class="sub">{{ $page['sub'] }}</p>
        <div class="actions">
            <a href="{{ url('/get/estimate') }}" class="btn btn-primary btn-lg">Get a free estimate</a>
            <a href="{{ url('/contact') }}" class="btn btn-outline btn-lg">Talk to us</a>
        </div>
    </div>
</section>

<section class="sol-body">
    <div class="container">
        <div class="sol-grid">
            <div>
                @foreach($page['sections'] as $s)
                <div class="sol-section">
                    <h2>{{ $s['h2'] }}</h2>
                    @if(!empty($s['p']))<p>{{ $s['p'] }}</p>@endif
                    @if(!empty($s['bullets']))
                    <ul>@foreach($s['bullets'] as $b)<li>{{ $b }}</li>@endforeach</ul>
                    @endif
                </div>
                @endforeach

                @if(!empty($page['faqs']))
                <div class="sol-section sol-faq">
                    <h2>Questions owners ask</h2>
                    @foreach($page['faqs'] as $f)
                    <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>
                    @endforeach
                </div>
                @endif
            </div>

            <aside class="sol-aside">
                <div class="sol-card">
                    <h3>See what your unit could earn</h3>
                    <p>A free estimate based on real bookings in comparable units. No commitment, no upfront fee.</p>
                    <a href="{{ url('/get/estimate') }}" class="btn btn-primary">Get a free estimate</a>
                </div>
                <div class="sol-card">
                    <h3>MOKA for</h3>
                    <div class="sol-links">
                        @foreach($all as $p)
                            <a href="{{ url('/solutions/' . $p['slug']) }}" class="{{ $p['slug'] === $page['slug'] ? 'cur' : '' }}">{{ $p['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>

        <div class="sol-cta">
            <h2>Ready to earn more from your property?</h2>
            <p>Superhost and Preferred Host, rated 4.9 out of 5 by guests. Commission-only, no upfront cost.</p>
            <a href="{{ url('/get/estimate') }}" class="btn btn-primary btn-lg" style="background:#fff;color:#0a8a72">Get a free estimate</a>
        </div>
    </div>
</section>


@endsection

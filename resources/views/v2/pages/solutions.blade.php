{{--
    MOKA v2 — Solutions index
    Route: /solutions → Auth\WebController@solutions → v2.pages.solutions
--}}
@extends('v2.partial.layout')
@section('title', 'Property Management Solutions in Malaysia: Airbnb, Short-Stay, Monthly Rental, Corporate Housing | MOKA')
@section('meta_description', 'MOKA for homeowners, investors, developers and agents: Airbnb and short-term rental management, monthly and weekly rental, corporate housing and renovation across Malaysia.')
@php $headerTransparent = false; @endphp

@section('head')
<style>
.sols-hero{padding:72px 0 30px;background:linear-gradient(180deg,#fff7ed 0%,#fff 100%)}
.sols-hero h1{font-size:clamp(30px,4vw,46px);letter-spacing:-.02em;margin:10px 0 12px}
.sols-hero h1 em{font-style:normal;color:var(--orange)}
.sols-hero p{font-size:17px;color:#4b5563;max-width:720px;line-height:1.6}
.sols-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;padding:20px 0 60px}
.sol-tile{display:block;background:#fff;border:1px solid #eef0f3;border-radius:16px;padding:22px;text-decoration:none;color:inherit;box-shadow:0 2px 12px rgba(0,0,0,.04);transition:transform .15s,box-shadow .15s}
.sol-tile:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(0,0,0,.08)}
.sol-tile .eyebrow{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#0a8a72}
.sol-tile h2{font-size:19px;margin:8px 0 8px;letter-spacing:-.01em}
.sol-tile h2 em{font-style:normal;color:var(--orange)}
.sol-tile p{font-size:14.5px;color:#4b5563;line-height:1.6;margin:0 0 12px}
.sol-tile span{font-weight:700;color:#0a8a72;font-size:14px}
</style>
@endsection

@section('content')
<section class="sols-hero">
    <div class="container">
        <span class="section-label" style="color:var(--orange)">Solutions</span>
        <h1>One team for <em>every way</em> a property can earn</h1>
        <p>Nightly, weekly, monthly or corporate. Owned by a family, an investor, a developer or introduced by an agent. Pick the page that fits you.</p>
    </div>
</section>
<section>
    <div class="container">
        <div class="sols-grid">
            @foreach($pages as $p)
            <a href="{{ url('/solutions/' . $p['slug']) }}" class="sol-tile">
                <div class="eyebrow">{{ $p['eyebrow'] }}</div>
                <h2>{!! $p['h1'] !!}</h2>
                <p>{{ \Illuminate\Support\Str::limit($p['sub'], 150) }}</p>
                <span>Read more →</span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endsection

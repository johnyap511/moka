{{--
    MOKA v2 — Solutions index
    Route: /solutions → Auth\WebController@solutions → v2.pages.solutions
--}}
@extends('v2.partial.layout')
@section('title', 'Property Management Solutions in Malaysia: Airbnb, Short-Stay, Monthly Rental, Corporate Housing | MOKA')
@section('meta_description', 'MOKA for homeowners, investors, developers and agents: Airbnb and short-term rental management, monthly and weekly rental, corporate housing and renovation across Malaysia.')
@php $headerTransparent = true; @endphp

@section('head')
<style>
.sols-hero h1 em{font-style:normal;color:var(--orange)}
.sols-hero .section-label{color:var(--orange)}
.sols-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:var(--space-5)}
.sol-tile{display:flex;flex-direction:column;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:var(--space-7);text-decoration:none;color:inherit;box-shadow:0 10px 30px rgba(0,0,0,.04);transition:transform var(--ease-normal),box-shadow var(--ease-normal),border-color var(--ease-normal)}
.sol-tile:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(0,0,0,.08);border-color:var(--orange)}
.sol-tile .eyebrow{font-size:var(--text-xs);font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--orange)}
.sol-tile h2{font-family:var(--font-display);font-size:1.35rem;line-height:1.25;color:var(--teal);margin:var(--space-3) 0 var(--space-3)}
.sol-tile h2 em{font-style:normal;color:var(--orange)}
.sol-tile p{font-size:var(--text-sm);color:var(--gray-600);line-height:1.6;margin:0 0 var(--space-5);flex:1}
.sol-tile span{font-weight:600;color:var(--teal);font-size:var(--text-sm)}
.sol-tile:hover span{color:var(--orange)}
@media (max-width:960px){.sols-grid{grid-template-columns:1fr 1fr}}
@media (max-width:640px){.sols-grid{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<section class="about-hero sols-hero">
    <div class="container">
        <div class="about-hero-content">
        <span class="section-label text-orange">Solutions</span>
        <h1 class="display-hero about-hero-headline">One team for <em>every way</em> a property can earn</h1>
        <p class="about-hero-sub">Nightly, weekly, monthly or corporate. Owned by a family, an investor, a developer, or introduced by an agent. Pick the page that fits you.</p>
        </div>
    </div>
</section>

<section class="section">
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

{{--
    MOKA v2 — About Page
    Route: /about → Auth\WebController@HomeAbout → v2.pages.about
--}}
@extends('v2.partial.layout')
@section('title', 'About MOKA — We Are Built on Hosting')
@section('meta_description', 'Learn about MOKA, Malaysia\'s leading short-stay property management company. Our story, our team, and our mission.')
@section('content')

<section class="page-hero">
    <div class="container" data-reveal>
        <span class="section-label" style="color:var(--orange); justify-content:center;">Our Story</span>
        <h1 class="display-xl" style="margin-top:var(--space-3);">We are MOKA</h1>
        <p class="subtitle">We're helping homeowners make more of their property. Unlock your potential by opening your doors to guests — host more, earn more, do more.</p>
    </div>
</section>

{{-- Opportunity --}}
<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="why-moka-row">
            <div class="why-moka-img" data-reveal="left">
                <img src="{{ asset('/new-theme23/images/Asset 17.png') }}" alt="Hosting opportunity" loading="lazy">
            </div>
            <div data-reveal="right">
                <span class="section-label text-orange">Opportunity</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">Hosting opens a world of opportunity.</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-4);">People have never been more free, and the world never more open. We choose our own paths and make our own chances.</p>
                <p class="body-lg" style="color:var(--gray-500);">In this era of opportunity, your home can be more than just bricks, mortar, and a mortgage. Host, and your home can earn that round-the-world trip, the time to pursue your passion, or the chance to start your own business.</p>
            </div>
        </div>
    </div>
</section>

{{-- The Challenge --}}
<section class="section" style="background:var(--cream);">
    <div class="container">
        <div class="why-moka-row reverse">
            <div class="why-moka-img" data-reveal="right">
                <img src="{{ asset('/new-theme23/images/Asset 18.png') }}" alt="Property management challenges" loading="lazy">
            </div>
            <div data-reveal="left">
                <span class="section-label text-orange">The Challenge</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">But it isn't always easy.</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-4);">Sourcing, managing and dealing with a constant changeover of tenants can be tricky. Responding to messages at 1am. Finding a good — and reliable — cleaner.</p>
                <p class="body-lg" style="color:var(--gray-500);">Without a host partner, you'll find that hosting guests from around the world doesn't free you, it ties you down. That's where MOKA comes in.</p>
            </div>
        </div>
    </div>
</section>

{{-- Stats --}}
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            @foreach([['100,000+','Trips hosted'],['70%','Occupancy rate'],['RM10M+','Revenue for hosts'],['4.9/5','Rating'],['Superhost','Awarded']] as $i => $s)
                <div class="stat-item" data-reveal data-delay="{{ $i*80 }}">
                    <span class="stat-number">{{ $s[0] }}</span>
                    <span class="stat-label">{{ $s[1] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Our Mission --}}
<section class="section" style="background:var(--teal);">
    <div class="container" style="text-align:center;">
        <div data-reveal>
            <span class="section-label" style="color:var(--orange); justify-content:center;">Our Mission</span>
            <h2 class="display-xl" style="color:var(--white); margin-block:var(--space-5) var(--space-6);">Making hosting effortless for every homeowner.</h2>
            <p style="color:rgba(255,255,255,0.65); font-size:var(--text-lg); line-height:1.7; max-width:620px; margin:0 auto var(--space-10);">MOKA was founded with a simple belief: that every homeowner deserves to benefit from the short-stay revolution — without it consuming their lives.</p>
            <a href="{{ url('/get/estimate') }}" target="_blank" class="btn btn-primary btn-lg">Start Your Journey →</a>
        </div>
    </div>
</section>

<section class="bottom-cta">
    <div class="container" data-reveal>
        <h2 class="display-xl">Ready to earn more from your property?</h2>
        <p class="subtitle">Join hundreds of Malaysian homeowners who trust MOKA.</p>
        <div class="btn-actions">
            <a href="{{ url('/get/estimate') }}" target="_blank" class="btn btn-teal btn-lg">Get Free Estimate</a>
            <a href="{{ url('/service') }}" class="btn btn-outline btn-lg">Our Services</a>
        </div>
    </div>
</section>

@endsection

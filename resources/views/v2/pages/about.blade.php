{{--
    MOKA v2 — About Page
    Route: /about → Auth\WebController@HomeAbout → v2.pages.about
--}}
@extends('v2.partial.layout')
@section('title', 'About MOKA — Malaysia\'s Leading Short-Stay Property Managers')
@section('meta_description', 'Learn about MOKA — how we started, what we believe, and the team that helps Malaysian homeowners earn more from their properties.')
@php $headerTransparent = true; @endphp

@section('content')

{{-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ --}}
<section class="about-hero">
    <div class="about-hero-bg" aria-hidden="true">
        <img src="{{ asset('/new-theme23/images/Asset 17.png') }}"
             alt=""
             loading="eager">
        <div class="about-hero-overlay"></div>
    </div>
    <div class="container">
        <div class="about-hero-content" data-reveal>
            <span class="section-label" style="color:var(--orange); justify-content:center;">Our Story</span>
            <h1 class="display-hero about-hero-headline">
                We are <em>MOKA</em>
            </h1>
            <p class="about-hero-sub">
                Helping Malaysian homeowners unlock the full potential of their properties — one perfectly hosted stay at a time.
            </p>
            <div class="about-hero-actions">
                <a href="{{ url('/get/estimate') }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                    Get Your Free Estimate
                </a>
                <a href="{{ url('/service') }}" class="btn btn-outline btn-lg">
                    Our Services
                </a>
            </div>
        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="scroll-indicator" aria-hidden="true">
        <span></span>
    </div>
</section>

{{-- ══════════════════════════════════════════
     STATS BAR
══════════════════════════════════════════ --}}
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            @foreach([
                ['100,000+', 'Trips Hosted'],
                ['70%',      'Avg. Occupancy'],
                ['RM10M+',   'Revenue for Hosts'],
                ['4.9★',     'Guest Rating'],
                ['Superhost','Status Awarded'],
            ] as $i => $s)
                <div class="stat-item" data-reveal data-delay="{{ $i * 80 }}">
                    <span class="stat-number">{{ $s[0] }}</span>
                    <span class="stat-label">{{ $s[1] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     OPPORTUNITY
══════════════════════════════════════════ --}}
<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="about-split-row">
            <div class="about-split-img" data-reveal="left">
                <img src="{{ asset('/new-theme23/images/Asset 17.png') }}"
                     alt="A beautifully managed MOKA property"
                     loading="lazy">
                <div class="about-split-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    <span>Up to 100% more income</span>
                </div>
            </div>
            <div class="about-split-text" data-reveal="right">
                <span class="section-label text-orange">Opportunity</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-4) var(--space-6);">
                    Your home can open a world of opportunity.
                </h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-5);">
                    People have never been more free, and the world never more open. We choose our own paths and make our own chances.
                </p>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-8);">
                    In this era of opportunity, your home can be more than bricks, mortar, and a mortgage. Host through MOKA — and your property can fund that round-the-world trip, the time to pursue your passion, or the start of something new.
                </p>
                <div class="about-checklist">
                    @foreach(['Professional management from day one', 'Listed on Airbnb, Booking.com & more', 'Dynamic pricing that maximises every night', 'Hotel-grade housekeeping after every stay'] as $point)
                        <div class="about-check-item">
                            <span class="about-check-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                            {{ $point }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     THE CHALLENGE
══════════════════════════════════════════ --}}
<section class="section" style="background:var(--cream);">
    <div class="container">
        <div class="about-split-row about-split-row--reversed">
            <div class="about-split-img" data-reveal="right">
                <img src="{{ asset('/new-theme23/images/Asset 18.png') }}"
                     alt="Property management without MOKA can be stressful"
                     loading="lazy">
            </div>
            <div class="about-split-text" data-reveal="left">
                <span class="section-label text-orange">The Challenge</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-4) var(--space-6);">
                    But it isn't always easy.
                </h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-5);">
                    Sourcing, managing, and dealing with a constant changeover of tenants can be overwhelming. Responding to messages at 1am. Finding a good — and reliable — cleaner.
                </p>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-8);">
                    Without a dedicated host partner, you'll find that opening your doors to guests from around the world doesn't set you free — it ties you down. That's exactly where MOKA comes in.
                </p>
                <a href="{{ url('/service') }}" class="btn btn-outline-teal btn-lg">
                    See How We Help →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     MISSION STATEMENT
══════════════════════════════════════════ --}}
<section class="about-mission section" style="background:var(--teal);">
    <div class="container" style="text-align:center;">
        <div data-reveal>
            <span class="section-label" style="color:var(--orange); justify-content:center;">Our Mission</span>
            <h2 class="display-xl" style="color:var(--white); margin-block:var(--space-6) var(--space-8);">
                Making hosting effortless<br>for every homeowner.
            </h2>
            <p class="about-mission-sub">
                MOKA was founded on a simple belief: that every Malaysian homeowner deserves to benefit from the short-stay revolution — without it consuming their life.
            </p>
            <div class="about-mission-values">
                @foreach([
                    ['M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'Trust',        'Every decision we make is built on transparency and honesty with our homeowners.'],
                    ['M13 2L3 14h9l-1 8 10-12h-9l1-8z',                   'Performance',  'We obsess over occupancy rates, pricing, and guest satisfaction to maximise your income.'],
                    ['M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',      'Care',         'Your property is treated with the same respect and attention as if it were our own.'],
                ] as [$icon, $title, $desc])
                    <div class="about-value-card" data-reveal>
                        <div class="about-value-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="{{ $icon }}"/>
                            </svg>
                        </div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     TEAM
══════════════════════════════════════════ --}}
<section class="section" style="background:var(--white);">
    <div class="container">
        <div style="text-align:center; margin-bottom:var(--space-16);" data-reveal>
            <span class="section-label text-orange" style="justify-content:center;">The People</span>
            <h2 class="display-xl text-teal" style="margin-top:var(--space-4);">Meet the team behind MOKA</h2>
            <p class="body-lg" style="color:var(--gray-500); max-width:520px; margin:var(--space-5) auto 0;">
                A dedicated group of hospitality professionals, technologists, and property experts united by one goal.
            </p>
        </div>

        <div class="team-grid">
            @foreach([
                ['Chief Executive Officer',      'MOKA Co-founder', 'M'],
                ['Chief Operating Officer',      'MOKA Co-founder', 'O'],
                ['Head of Guest Experience',     'Team MOKA',       'K'],
                ['Head of Revenue Management',   'Team MOKA',       'A'],
                ['Property Operations Manager',  'Team MOKA',       'T'],
                ['Head of Housekeeping',         'Team MOKA',       'E'],
            ] as $i => [$role, $team, $initial])
                <div class="team-card" data-reveal data-delay="{{ $i * 80 }}">
                    <div class="team-card-avatar">
                        <span class="team-avatar-initial">{{ $initial }}</span>
                    </div>
                    <div class="team-card-info">
                        <p class="team-card-role">{{ $role }}</p>
                        <span class="team-card-team">{{ $team }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     TIMELINE / MILESTONES
══════════════════════════════════════════ --}}
<section class="section" style="background:var(--cream);">
    <div class="container">
        <div style="text-align:center; margin-bottom:var(--space-16);" data-reveal>
            <span class="section-label text-orange" style="justify-content:center;">Our Journey</span>
            <h2 class="display-xl text-teal" style="margin-top:var(--space-4);">Built milestone by milestone</h2>
        </div>

        <div class="timeline">
            @foreach([
                ['2018', 'MOKA Founded',         'Started with 5 properties in Kuala Lumpur, driven by a belief that hosting should be effortless.'],
                ['2019', 'First 100 Properties', 'Rapid growth across the Klang Valley as word spread about our hands-off management model.'],
                ['2020', 'Pandemic Pivot',        'Adapted swiftly to evolving travel patterns, supporting hosts through uncertainty and securing long-stay bookings.'],
                ['2021', 'Superhost Status',      'Achieved Airbnb Superhost status — recognising our consistently exceptional guest experience standards.'],
                ['2022', 'RM10M Revenue Milestone','Crossed RM10 million in cumulative revenue generated for Malaysian homeowners through the MOKA platform.'],
                ['2023', 'Malaysia Expansion',   'Expanded beyond KL to Penang, Johor Bahru, and Kota Kinabalu — growing the MOKA family nationwide.'],
                ['2024', 'MOKA v2 Launch',       'Launched a reimagined platform giving owners full visibility into performance, bookings, and revenue in real time.'],
            ] as $i => [$year, $title, $desc])
                <div class="timeline-item {{ $i % 2 === 0 ? 'timeline-item--left' : 'timeline-item--right' }}" data-reveal data-delay="{{ min($i * 80, 400) }}">
                    <div class="timeline-content">
                        <span class="timeline-year">{{ $year }}</span>
                        <h3 class="timeline-title">{{ $title }}</h3>
                        <p class="timeline-desc">{{ $desc }}</p>
                    </div>
                    <div class="timeline-node" aria-hidden="true"></div>
                </div>
            @endforeach
            <div class="timeline-line" aria-hidden="true"></div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     AWARDS & MEDIA
══════════════════════════════════════════ --}}
<section class="section-sm" style="background:var(--white);">
    <div class="container" data-reveal>
        <div style="text-align:center; margin-bottom:var(--space-12);">
            <span class="section-label text-orange" style="justify-content:center;">Recognition</span>
            <h2 class="display-md text-teal" style="margin-top:var(--space-4);">Awards & media</h2>
        </div>
        <div class="awards-grid">
            @foreach(['Airbnb Superhost', 'Booking.com Partner', 'The Star Business', 'EdgeProp', 'iProperty', 'Vulcan Post'] as $i => $award)
                <div class="award-item" data-reveal data-delay="{{ $i * 60 }}">
                    <span>{{ $award }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     BOTTOM CTA
══════════════════════════════════════════ --}}
<section class="bottom-cta">
    <div class="container" data-reveal>
        <span class="section-label" style="color:var(--orange); justify-content:center;">Ready?</span>
        <h2 class="display-xl">Earn more from your property.</h2>
        <p class="subtitle">Join hundreds of Malaysian homeowners who trust MOKA to make hosting effortless.</p>
        <div class="btn-actions">
            <a href="{{ url('/get/estimate') }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">Get Free Estimate</a>
            <a href="{{ url('/service') }}" class="btn btn-outline btn-lg">Our Services</a>
        </div>
    </div>
</section>

@endsection

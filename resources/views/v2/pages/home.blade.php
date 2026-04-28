@extends('v2.partial.layout')

@section('title', 'MOKA — Hosting. It\'s what we do. | Property Management Malaysia')
@section('meta_description', 'MOKA is Malaysia\'s leading short-stay property management company. Earn up to 100% more from your property. Get a free income estimate today.')

@php $headerTransparent = true; @endphp

@section('content')

{{-- ════════════════════════════════════════════
     HERO
════════════════════════════════════════════ --}}
<section class="hero" id="hero">
    <div class="container">
        <div class="hero-content">

            <!-- Left: Headline + CTA -->
            <div>
                <div class="hero-eyebrow animate-fadeInUp">
                    <span></span>
                    Malaysia's #1 Short-Stay Management
                </div>

                <h1 class="hero-title animate-fadeInUp delay-100">
                    Hosting.<br>
                    <em>It's what</em><br>
                    we do.
                </h1>

                <p class="hero-subtitle animate-fadeInUp delay-200">
                    Professionally managed flexible lettings.
                    Together, we'll earn more from your property —
                    without the hassle.
                </p>

                <div class="hero-actions animate-fadeInUp delay-300">
                    <a href="{{ url('/get/estimate') }}" target="_blank" class="btn btn-primary btn-lg">
                        Get Free Estimate
                    </a>
                    <a href="{{ url('/service') }}" class="btn btn-outline btn-lg">
                        Our Services
                    </a>
                </div>
            </div>

            <!-- Right: Estimate Card -->
            <div class="animate-fadeInRight delay-200">
                <div class="hero-estimate-card">
                    <p class="estimate-card-label">Income Estimate</p>
                    <h2 class="estimate-card-title">How much could your property earn?</h2>

                    <form method="POST" action="{{ url('/estimate') }}" class="estimate-form" novalidate>
                        @csrf
                        <input type="hidden" name="type" value="estimate">
                        <input type="hidden" name="bedroom" value="1" id="bedroomHidden">

                        <div>
                            <label class="form-label" for="estName">Your name</label>
                            <input id="estName" class="form-input" type="text" name="name" placeholder="Full name" required>
                        </div>

                        <div>
                            <label class="form-label" for="estAddress">Property address</label>
                            <input id="estAddress" class="form-input" type="text" name="address" placeholder="e.g. Damansara, KL" required>
                        </div>

                        <div>
                            <label class="form-label" for="estEmail">Email address</label>
                            <input id="estEmail" class="form-input" type="email" name="email" placeholder="you@example.com" required>
                        </div>

                        <div>
                            <label class="form-label" for="estPhone">Mobile number</label>
                            <input id="estPhone" class="form-input" type="tel" name="phone" placeholder="+60 12 345 6789" required>
                        </div>

                        <div>
                            <label class="form-label">Number of bedrooms</label>
                            <div class="bedroom-counter">
                                <button type="button" class="bedroom-counter-btn" data-action="minus" aria-label="Decrease bedrooms">−</button>
                                <span class="bedroom-counter-value">1 Bedroom</span>
                                <button type="button" class="bedroom-counter-btn" data-action="plus" aria-label="Increase bedrooms">+</button>
                            </div>
                        </div>

                        <div class="estimate-checkbox-row">
                            <input type="checkbox" id="privacyCheck" required>
                            <label for="privacyCheck">
                                By submitting, I accept MOKA's
                                <a href="{{ url('/policy') }}" style="color:var(--orange);">privacy policy</a>.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Get Estimate Now →
                        </button>
                    </form>

                    <div class="estimate-card-or" style="margin-block:var(--space-4);">or</div>

                    <a href="https://wa.me/message/GJMYMABOT7CSG1"
                       target="_blank" rel="noopener"
                       class="whatsapp-btn">
                        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.119.553 4.107 1.522 5.834L0 24l6.335-1.502A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.667-.493-5.209-1.355l-.374-.222-3.755.89.949-3.658-.244-.389A9.939 9.939 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        Book a Free Consultation
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     STATS BAR
════════════════════════════════════════════ --}}
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            @php
            $stats = [
                ['number' => '20,000+', 'label' => 'Trips hosted'],
                ['number' => '70%',     'label' => 'Occupancy rate'],
                ['number' => 'RM10M+',  'label' => 'Revenue for hosts'],
                ['number' => '4.9/5',   'label' => 'Overall rating'],
                ['number' => 'Superhost','label' => 'Airbnb award'],
            ];
            @endphp
            @foreach($stats as $i => $stat)
                <div class="stat-item" data-reveal data-delay="{{ $i * 80 }}">
                    <span class="stat-number">{{ $stat['number'] }}</span>
                    <span class="stat-label">{{ $stat['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     WHY MOKA
════════════════════════════════════════════ --}}
<section class="why-moka section">
    <div class="container">
        <div style="text-align:center; margin-bottom:var(--space-16);" data-reveal>
            <span class="section-label text-orange">Why Choose MOKA?</span>
            <h2 class="display-xl text-teal" style="margin-top:var(--space-3);">We make your property<br>work harder</h2>
        </div>

        <div class="why-moka-grid">

            <!-- Row 1 -->
            <div class="why-moka-row">
                <div class="why-moka-img" data-reveal="left">
                    <img src="{{ asset('/new-theme23/images/Asset 2.png') }}" alt="Double your rental revenue" loading="lazy">
                </div>
                <div class="why-moka-text" data-reveal="right">
                    <span class="section-label text-orange">Revenue</span>
                    <h3 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">We Double Up Your Rental Revenue</h3>
                    <p class="body-lg" style="color:var(--gray-500);">Our tried and tested strategy can earn you up to 100% more than just Airbnb or traditional tenancies alone. Multi-platform marketing and hotel-grade revenue management.</p>
                    <a href="{{ url('/service') }}" class="btn btn-outline-teal btn-sm" style="margin-top:var(--space-6);">Learn More →</a>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="why-moka-row reverse">
                <div class="why-moka-img" data-reveal="right">
                    <img src="{{ asset('/new-theme23/images/Asset 48.png') }}" alt="Total Renovation Solutions" loading="lazy">
                </div>
                <div class="why-moka-text" data-reveal="left">
                    <span class="section-label text-orange">Renovation</span>
                    <h3 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">Maximising Property Value with Total Renovation</h3>
                    <p class="body-lg" style="color:var(--gray-500);">With our complete renovation services, your property not only looks fantastic but commands a higher market price and higher nightly rates.</p>
                    <a href="{{ url('/designs') }}" class="btn btn-outline-teal btn-sm" style="margin-top:var(--space-6);">View Our Designs →</a>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="why-moka-row">
                <div class="why-moka-img" data-reveal="left">
                    <img src="{{ asset('/new-theme23/images/Asset 3.png') }}" alt="Hybrid Rental Solution" loading="lazy">
                </div>
                <div class="why-moka-text" data-reveal="right">
                    <span class="section-label text-orange">Flexibility</span>
                    <h3 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">A Hybrid Rental Solution</h3>
                    <p class="body-lg" style="color:var(--gray-500);">Short-stay, mid-stay and long-stay — we blend the right mix to maximise your occupancy and revenue all year round. More opportunities, more profit.</p>
                    <a href="{{ url('/homepage') }}" class="btn btn-outline-teal btn-sm" style="margin-top:var(--space-6);">Find Out More →</a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     OUR SERVICES
════════════════════════════════════════════ --}}
<section class="services section">
    <div class="container">
        <div style="text-align:center; margin-bottom:var(--space-12);" data-reveal>
            <span class="section-label text-orange">What We Offer</span>
            <h2 class="display-xl text-teal" style="margin-top:var(--space-3);">A complete hassle-free solution</h2>
        </div>

        <div class="services-grid">
            @php
            $services = [
                ['icon' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z', 'icon2' => 'M9 22V12h6v10', 'title' => 'Renovation', 'desc' => 'Home makeover from consultation to finalisation — designed for maximum rental appeal.'],
                ['icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2', 'icon2' => 'M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', 'title' => 'Guest Hosting', 'desc' => '24-hour booking support, guest check-ins, and 5-star guest experience management.'],
                ['icon' => 'M12 20V10', 'icon2' => 'M18 20V4M6 20v-6', 'title' => 'Revenue Management', 'desc' => 'Multi-platform marketing & complete PMS system to maximise your nightly rate.'],
                ['icon' => 'M3 3h18v18H3zM3 9h18M9 21V9', 'title' => 'Housekeeping', 'desc' => 'Hotel-standard housekeeping and 24/7 maintenance support team always on standby.'],
            ];
            @endphp

            @foreach($services as $i => $svc)
                <div class="service-card" data-reveal data-delay="{{ $i * 100 }}">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $svc['icon'] }}"/>
                            @if(!empty($svc['icon2']))<path d="{{ $svc['icon2'] }}"/>@endif
                        </svg>
                    </div>
                    <h3>{{ $svc['title'] }}</h3>
                    <p>{{ $svc['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <div style="text-align:center; margin-top:var(--space-12);" data-reveal>
            <a href="{{ url('/service') }}" class="btn btn-teal btn-lg">Explore All Services</a>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     HOW WE WORK
════════════════════════════════════════════ --}}
<section class="how-it-works section">
    <div class="container">
        <div data-reveal style="max-width:600px;">
            <span class="section-label" style="color:var(--orange);">Simple Process</span>
            <h2 class="display-xl" style="color:var(--white); margin-top:var(--space-3);">How We Work</h2>
            <p class="body-lg" style="color:rgba(255,255,255,0.6); margin-top:var(--space-4);">Our simple 4-step process gets your property earning more in no time.</p>
        </div>

        <div class="steps-grid">
            @php
            $steps = [
                ['num' => '01', 'icon' => 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z', 'title' => 'Site Inspection', 'desc' => 'Professional consultation to determine the best rental strategy for your property.'],
                ['num' => '02', 'icon' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z', 'title' => 'Renovation', 'desc' => 'We make over your home with purpose — designed to maximise booking appeal.'],
                ['num' => '03', 'icon' => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5', 'title' => 'List & Rent', 'desc' => 'Multi-platform listing with dynamic pricing for high occupancy and conversion.'],
                ['num' => '04', 'icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2', 'title' => 'We Manage All', 'desc' => 'Guests, cleaning, maintenance — all handled by our professional team.'],
            ];
            @endphp

            @foreach($steps as $i => $step)
                <div class="step-card" data-reveal data-delay="{{ $i * 120 }}">
                    <span class="step-number">{{ $step['num'] }}</span>
                    <div class="step-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $step['icon'] }}"/>
                        </svg>
                    </div>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <div style="margin-top:var(--space-12); display:flex; justify-content:center;" data-reveal>
            <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                Chat With Us Now
            </a>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     DASHBOARD TEASER
════════════════════════════════════════════ --}}
<section class="dashboard-teaser section">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr; gap:var(--space-12); align-items:center;"
             class="why-moka-row">

            <div data-reveal="left">
                <span class="section-label text-orange">Owner Dashboard</span>
                <h2 class="display-xl text-teal" style="margin-block:var(--space-3) var(--space-5);">Smarter Management with MOKA Dashboard</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-8);">The hub. The HQ. The nexus. We've built a powerful platform and put your home at the heart of it. Full visibility, full control.</p>

                <div class="dashboard-features">
                    @foreach(['Live Booking Overview', 'Calendar & Availability', 'Revenue Performance', 'Cleaning Schedule', 'Maintenance Tracker'] as $i => $feat)
                        <div class="dashboard-feature" data-reveal data-delay="{{ $i * 80 }}">
                            <span class="feature-dot"></span>
                            <span>{{ $feat }}</span>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:var(--space-8);">
                    <button class="btn btn-teal" data-open-modal="auth">Access Your Dashboard →</button>
                </div>
            </div>

            <div class="dashboard-img" data-reveal="right">
                <img src="{{ asset('/new-theme23/images/Asset 5.png') }}"
                     alt="MOKA Owner Dashboard"
                     style="width:70%; opacity:0.85;"
                     loading="lazy">
            </div>

        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     PARTNERS
════════════════════════════════════════════ --}}
<section class="partners section-sm">
    <div class="container">
        <div style="text-align:center;" data-reveal>
            <span class="section-label text-orange">Trusted Platforms</span>
            <h2 class="display-md text-teal" style="margin-top:var(--space-3);">Our Partners</h2>
        </div>
        <div class="partners-logos" data-reveal>
            <img src="{{ asset('/new-theme23/images/Asset 6.png') }}"
                 alt="MOKA partner platforms"
                 style="max-width:700px; width:100%; opacity:0.7; filter:grayscale(20%);"
                 loading="lazy">
        </div>
        <div style="text-align:center; margin-top:var(--space-10);" data-reveal>
            <a href="{{ url('/about') }}" class="btn btn-outline-teal">About Us →</a>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════
     BOTTOM CTA
════════════════════════════════════════════ --}}
<section class="bottom-cta">
    <div class="container" data-reveal>
        <h2 class="display-xl">Find out how much your property could earn!</h2>
        <p class="subtitle">Takes just 30 seconds. Completely free.</p>
        <div class="btn-actions">
            <a href="{{ url('/get/estimate') }}" target="_blank" class="btn btn-teal btn-lg">Get a Quick Estimate (Free)</a>
            <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener" class="btn btn-outline btn-lg">Chat With Us</a>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<style>
    /* Override dashboard-teaser grid for large screens */
    @media (min-width: 768px) {
        .dashboard-teaser .why-moka-row {
            grid-template-columns: 1fr 1fr !important;
        }
    }
</style>
@endsection

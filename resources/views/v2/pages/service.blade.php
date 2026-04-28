@extends('v2.partial.layout')
@section('title', 'Our Services — Complete Short-Stay Management | MOKA')
@section('meta_description', 'MOKA offers full-service short-stay property management in Malaysia — renovation, guest hosting, revenue management, and housekeeping.')
@section('content')

<section class="page-hero">
    <div class="container" data-reveal>
        <span class="section-label" style="color:var(--orange); justify-content:center;">What We Offer</span>
        <h1 class="display-xl" style="margin-top:var(--space-3);">Rest easy with our complete service</h1>
        <p class="subtitle">We're the professional short-let management team who optimise your pricing and occupancy while handling everything for you.</p>
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

{{-- Services Detail --}}
<section class="section" style="background:var(--white);">
    <div class="container">
        <div style="text-align:center; margin-bottom:var(--space-12);" data-reveal>
            <span class="section-label text-orange">Full Management</span>
            <h2 class="display-xl text-teal" style="margin-top:var(--space-3);">Everything under one roof</h2>
        </div>

        <div class="services-detail-grid">

            <!-- Renovation -->
            <div class="service-detail-card" data-reveal>
                <div class="service-detail-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <h3>Total Renovation Solutions</h3>
                <p>With our complete renovation services, you'll enjoy a property that not only looks fantastic but also commands a higher market price and nightly rate.</p>
                <div class="service-list">
                    @foreach(['Interior design consultation','Full renovation management','Furniture sourcing & staging','Photography & listing optimisation','Renovation for Airbnb compliance'] as $item)
                        <div class="service-list-item">
                            <span class="service-list-check"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Guest Hosting -->
            <div class="service-detail-card" data-reveal data-delay="100">
                <div class="service-detail-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Guest Hosting & Management</h3>
                <p>24-hour booking service support and guest check-ins — we handle every interaction so you don't have to wake up at 3am.</p>
                <div class="service-list">
                    @foreach(['24/7 guest communication','Seamless check-in & check-out','Guest vetting & screening','Emergency response team','Guest satisfaction management'] as $item)
                        <div class="service-list-item">
                            <span class="service-list-check"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Revenue Management -->
            <div class="service-detail-card" data-reveal data-delay="200">
                <div class="service-detail-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h3>Hotel Revenue Management</h3>
                <p>Multi-platform marketing and complete PMS system — we list on Airbnb, Booking.com, and more, with dynamic pricing that maximises your revenue.</p>
                <div class="service-list">
                    @foreach(['Airbnb & Booking.com listing','Dynamic pricing strategy','Multi-channel distribution','Performance analytics','Revenue reporting dashboard'] as $item)
                        <div class="service-list-item">
                            <span class="service-list-check"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Housekeeping -->
            <div class="service-detail-card" data-reveal data-delay="300">
                <div class="service-detail-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </div>
                <h3>Housekeeping & Maintenance</h3>
                <p>Hotel-standard housekeeping and a 24/7 support team on standby — your property is always guest-ready and maintained to the highest standard.</p>
                <div class="service-list">
                    @foreach(['Hotel-grade cleaning standards','Linen & towel management','Restocking of supplies','Preventive maintenance','24/7 emergency repairs'] as $item)
                        <div class="service-list-item">
                            <span class="service-list-check"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>

{{-- How We Work --}}
<section class="how-it-works section">
    <div class="container">
        <div data-reveal style="max-width:600px;">
            <span class="section-label" style="color:var(--orange);">Simple Process</span>
            <h2 class="display-xl" style="color:var(--white); margin-top:var(--space-3);">Getting started is easy</h2>
            <p class="body-lg" style="color:rgba(255,255,255,0.6); margin-top:var(--space-4);">Our 4-step onboarding gets your property earning in weeks, not months.</p>
        </div>
        <div class="steps-grid">
            @foreach([
                ['01','M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z','Site Inspection','Professional consultation — we visit your property and assess its rental potential.'],
                ['02','M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z','Renovation','We transform your space with purpose-built design to maximise booking appeal.'],
                ['03','M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5','List & Rent','Multi-platform listing, professional photography, and dynamic pricing strategy.'],
                ['04','M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2','Sit Back & Earn','We manage guests, cleaning, and maintenance. You collect the revenue.'],
            ] as $i => [$num, $icon, $title, $desc])
                <div class="step-card" data-reveal data-delay="{{ $i*120 }}">
                    <span class="step-number">{{ $num }}</span>
                    <div class="step-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="{{ $icon }}"/></svg>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="bottom-cta">
    <div class="container" data-reveal>
        <h2 class="display-xl">Ready to get started?</h2>
        <p class="subtitle">Get a free income estimate for your property in 30 seconds.</p>
        <div class="btn-actions">
            <a href="{{ url('/get/estimate') }}" target="_blank" class="btn btn-teal btn-lg">Get Free Estimate</a>
            <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener" class="btn btn-outline btn-lg">Chat With Us</a>
        </div>
    </div>
</section>

@endsection

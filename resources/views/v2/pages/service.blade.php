@extends('v2.partial.layout')

@section('title', 'Our Services — Complete Short-Stay Management | MOKA')
@section('meta_description', 'MOKA offers full-service short-stay property management in Malaysia — renovation, guest hosting, revenue management, and housekeeping.')

@section('content')

{{-- Hero --}}
<section class="service-hero">
    <div class="container">
        <div data-reveal>
            <span class="section-label" style="color:var(--orange);">What We Do</span>
            <h1>A complete solution,<br>handled end-to-end.</h1>
            <p>From renovation to guest check-out — we manage everything so you earn more without lifting a finger.</p>
        </div>
    </div>
</section>

{{-- Services Detail --}}
<section class="services-detail">
    <div class="container">

        {{-- Renovation --}}
        <div class="service-detail-row">
            <div class="service-detail-img" data-reveal="left">
                <img src="{{ asset('/new-theme23/images/Asset 2.png') }}" alt="Renovation" loading="lazy">
            </div>
            <div data-reveal="right">
                <span class="section-label text-orange">01 — Renovation</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">Maximising Property Value with Total Renovation</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-5);">Our in-house renovation team transforms your property into a high-earning short-stay asset — designed for maximum appeal and durability.</p>
                <ul class="service-bullets">
                    <li>Full interior design consultation included</li>
                    <li>Furniture & fittings sourcing and installation</li>
                    <li>Smart home setup (smart locks, wifi, etc.)</li>
                    <li>Photography-ready staging on completion</li>
                    <li>Post-renovation touch-ups at no extra charge</li>
                </ul>
            </div>
        </div>

        {{-- Guest Hosting --}}
        <div class="service-detail-row reverse">
            <div class="service-detail-img" data-reveal="right">
                <img src="{{ asset('/new-theme23/images/Asset 48.png') }}" alt="Guest Hosting" loading="lazy">
            </div>
            <div data-reveal="left">
                <span class="section-label text-orange">02 — Guest Hosting</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">5-Star Guest Experience, Around the Clock</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-5);">Our dedicated hosting team manages every guest interaction — from inquiry to check-out — ensuring 5-star reviews every time.</p>
                <ul class="service-bullets">
                    <li>24/7 guest communication & support</li>
                    <li>Seamless check-in with smart locks</li>
                    <li>Welcome packs & local recommendations</li>
                    <li>Issue resolution within 1 hour</li>
                    <li>Review management & response strategy</li>
                </ul>
            </div>
        </div>

        {{-- Revenue Management --}}
        <div class="service-detail-row">
            <div class="service-detail-img" data-reveal="left">
                <img src="{{ asset('/new-theme23/images/Asset 3.png') }}" alt="Revenue Management" loading="lazy">
            </div>
            <div data-reveal="right">
                <span class="section-label text-orange">03 — Revenue Management</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">Multi-Platform Marketing & Dynamic Pricing</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-5);">We list your property across Airbnb, Booking.com, Agoda and more — with dynamic pricing that adjusts nightly rates in real time.</p>
                <ul class="service-bullets">
                    <li>Listed on 5+ platforms simultaneously</li>
                    <li>AI-powered dynamic pricing engine</li>
                    <li>Professional photography & copywriting</li>
                    <li>Occupancy rate targeting above 70%</li>
                    <li>Monthly performance reports & insights</li>
                </ul>
            </div>
        </div>

        {{-- Housekeeping --}}
        <div class="service-detail-row reverse">
            <div class="service-detail-img" data-reveal="right">
                <img src="{{ asset('/new-theme23/images/Asset 5.png') }}" alt="Housekeeping" loading="lazy">
            </div>
            <div data-reveal="left">
                <span class="section-label text-orange">04 — Housekeeping</span>
                <h2 class="display-md text-teal" style="margin-block:var(--space-3) var(--space-5);">Hotel-Standard Cleaning After Every Stay</h2>
                <p class="body-lg" style="color:var(--gray-500); margin-bottom:var(--space-5);">Our trained housekeeping team ensures your property is hotel-ready for every guest — every single time.</p>
                <ul class="service-bullets">
                    <li>Turnover cleaning after each checkout</li>
                    <li>Fresh linen & towel service</li>
                    <li>Restocking of guest amenities</li>
                    <li>Deep cleaning monthly</li>
                    <li>24/7 maintenance support on standby</li>
                </ul>
            </div>
        </div>

    </div>
</section>

{{-- FAQ --}}
<section class="faq-section section">
    <div class="container">
        <div style="text-align:center;" data-reveal>
            <span class="section-label text-orange">Common Questions</span>
            <h2 class="display-xl text-teal" style="margin-top:var(--space-3);">Frequently Asked Questions</h2>
        </div>

        <div class="faq-list" data-reveal-stagger>
            @php
            $faqs = [
                ['q' => 'How much does MOKA charge?', 'a' => 'MOKA operates on a commission-based model — we only earn when you earn. Our fee is a percentage of your rental revenue, with no upfront costs. Contact us for a personalised quote based on your property.'],
                ['q' => 'Do I need to renovate before MOKA can manage my property?', 'a' => 'Not necessarily. We assess each property individually. Some properties are ready to list immediately, while others benefit from renovation to maximise earning potential. We provide an honest recommendation after the initial inspection.'],
                ['q' => 'How long does it take to get started?', 'a' => 'From signing with MOKA to your first booking is typically 2–4 weeks for properties that are ready, or 6–10 weeks if renovation is involved.'],
                ['q' => 'Can I still use my property when I want?', 'a' => 'Absolutely. Owner stays are always accommodated. Simply block your preferred dates in the MOKA dashboard and we manage everything around your schedule.'],
                ['q' => 'How do I track my property\'s performance?', 'a' => 'You get full access to the MOKA Owner Dashboard — a real-time portal showing bookings, revenue, occupancy, cleaning schedules, and maintenance logs.'],
                ['q' => 'Which areas does MOKA operate in?', 'a' => 'MOKA currently operates across Klang Valley, Penang, Johor Bahru, and Kota Kinabalu. We are expanding to new regions — contact us to check availability in your area.'],
            ];
            @endphp

            @foreach($faqs as $i => $faq)
                <div class="faq-item" data-reveal data-delay="{{ $i * 60 }}">
                    <button class="faq-question" aria-expanded="false">
                        {{ $faq['q'] }}
                        <span class="faq-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" role="region">
                        <div class="faq-answer-inner">{{ $faq['a'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Bottom CTA --}}
<section class="bottom-cta">
    <div class="container" data-reveal>
        <h2>Ready to earn more from your property?</h2>
        <p class="subtitle">Get a free income estimate in 30 seconds.</p>
        <div class="btn-actions">
            <a href="{{ url('/get/estimate') }}" target="_blank" class="btn btn-teal btn-lg">Get Free Estimate</a>
            <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener" class="btn btn-outline btn-lg">Chat With Us</a>
        </div>
    </div>
</section>

@endsection

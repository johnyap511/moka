@extends('v2.partial.layout')
@section('title', 'Get a Free Property Estimate — MOKA')
@section('meta_description', 'Find out how much your property could earn as a short-stay rental. Free estimate in 30 seconds.')
@php $headerTransparent = false; @endphp

@section('content')
<div class="estimate-page">
    <div class="container">
        <div class="estimate-page-inner">

            <!-- Left: Value Props -->
            <div class="estimate-page-left">
                <div data-reveal>
                    <span class="section-label" style="color:var(--orange);">Free & No Obligation</span>
                    <h1 class="display-xl" style="color:var(--white); margin-block:var(--space-4) var(--space-5);">Find out what your property could earn</h1>
                    <p class="subtitle">Get a personalised income estimate based on real data from similar properties managed by MOKA. Takes just 30 seconds.</p>
                </div>

                <div class="estimate-usp-list" style="margin-top:var(--space-10);">
                    @foreach([
                        ['M13 2L3 14h9l-1 8 10-12h-9l1-8z', 'Earn More Work Less',     'Our hybrid model earns homeowners up to 100% more than traditional rentals alone.'],
                        ['M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z', 'Complete Management', 'We handle guests, cleaning, maintenance and marketing — end to end.'],
                        ['M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z', 'You\'re in Control',   'Your property, your rules. Full transparency via your MOKA owner dashboard.'],
                    ] as $i => [$icon, $title, $desc])
                        <div class="estimate-usp-item" data-reveal data-delay="{{ $i * 100 }}">
                            <div class="usp-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"><path d="{{ $icon }}"/></svg>
                            </div>
                            <div class="usp-text">
                                <h4>{{ $title }}</h4>
                                <p>{{ $desc }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right: Form Card -->
            <div data-reveal="right">
                <div class="estimate-page-card">
                    <p class="estimate-card-label">Free Income Estimate</p>
                    <h2 class="estimate-card-title">How much could your property earn?</h2>

                    <form method="POST" action="{{ url('/estimate') }}" class="estimate-form" novalidate>
                        @csrf
                        <input type="hidden" name="type" value="estimate">
                        <input type="hidden" name="bedroom" value="1" id="bedroomHiddenPage">

                        <div>
                            <label class="form-label" for="pgName">Your name</label>
                            <input id="pgName" class="form-input" type="text" name="name" placeholder="Full name" required>
                        </div>
                        <div>
                            <label class="form-label" for="pgAddress">Property address</label>
                            <input id="pgAddress" class="form-input" type="text" name="address" placeholder="e.g. Damansara Heights, KL" required>
                        </div>
                        <div>
                            <label class="form-label" for="pgEmail">Email address</label>
                            <input id="pgEmail" class="form-input" type="email" name="email" placeholder="you@example.com" required>
                        </div>
                        <div>
                            <label class="form-label" for="pgPhone">Mobile number</label>
                            <input id="pgPhone" class="form-input" type="tel" name="phone" placeholder="+60 12 345 6789" required>
                        </div>
                        <div>
                            <label class="form-label">Number of bedrooms</label>
                            <div class="bedroom-counter">
                                <button type="button" class="bedroom-counter-btn" data-action="minus">−</button>
                                <span class="bedroom-counter-value">1 Bedroom</span>
                                <button type="button" class="bedroom-counter-btn" data-action="plus">+</button>
                            </div>
                        </div>
                        <div class="estimate-checkbox-row">
                            <input type="checkbox" id="privacyPage" required>
                            <label for="privacyPage">I accept MOKA's <a href="{{ url('/policy') }}" style="color:var(--orange);">privacy policy</a>.</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg" style="justify-content:center;">
                            Get My Estimate →
                        </button>
                    </form>

                    <div class="estimate-card-or" style="margin-block:var(--space-5);">or</div>

                    <a href="https://wa.me/message/GJMYMABOT7CSG1"
                       target="_blank" rel="noopener"
                       class="whatsapp-btn">
                        <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.119.553 4.107 1.522 5.834L0 24l6.335-1.502A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.667-.493-5.209-1.355l-.374-.222-3.755.89.949-3.658-.244-.389A9.939 9.939 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        Book a Free Consultation via WhatsApp
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

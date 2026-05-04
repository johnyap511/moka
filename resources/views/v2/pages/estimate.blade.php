@extends('v2.partial.layout')

@section('title', 'Get a Free Property Estimate — MOKA')
@section('meta_description', 'Find out how much your property could earn as a short-stay rental. Free estimate in 30 seconds.')

@php $headerTransparent = false; @endphp

@section('content')

<div class="estimate-page">
    <div class="container">
        <div class="estimate-container">

            {{-- Header --}}
            <div style="text-align:center; margin-bottom:var(--space-10);" data-reveal>
                <span class="section-label text-orange">Free Estimate</span>
                <h1 class="display-xl text-teal" style="margin-top:var(--space-3);">How much could your property earn?</h1>
                <p class="body-lg" style="color:var(--gray-500); margin-top:var(--space-4); max-width:480px; margin-inline:auto;">
                    Takes 30 seconds. No commitment required.
                </p>
            </div>

            {{-- Progress --}}
            <div class="estimate-progress" data-reveal>
                <div class="progress-step active">
                    <div class="progress-step-dot">1</div>
                    <span class="progress-step-label">Property</span>
                </div>
                <div class="progress-line"><div class="progress-line-fill"></div></div>
                <div class="progress-step">
                    <div class="progress-step-dot">2</div>
                    <span class="progress-step-label">Contact</span>
                </div>
                <div class="progress-line"><div class="progress-line-fill"></div></div>
                <div class="progress-step">
                    <div class="progress-step-dot">3</div>
                    <span class="progress-step-label">Done</span>
                </div>
            </div>

            {{-- Form Card --}}
            <div class="estimate-step-card" data-reveal>

                <form method="POST"
                      action="{{ url('/estimate') }}"
                      class="estimate-form-steps"
                      novalidate>
                    @csrf

                    {{-- Step 1: Property --}}
                    <div class="estimate-step active">
                        <h2>Tell us about<br>your property</h2>
                        <p>We need a few details to calculate your estimate accurately.</p>

                        <div class="estimate-fields">
                            <div class="form-group">
                                <label class="form-label" for="estAddress">Property address or area</label>
                                <input id="estAddress"
                                       class="form-input"
                                       type="text"
                                       name="address"
                                       placeholder="e.g. Damansara Perdana, KL"
                                       value="{{ old('address') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Number of bedrooms</label>
                                <div class="bedroom-counter">
                                    <button type="button" class="bedroom-counter-btn" data-action="minus" aria-label="Decrease bedrooms">−</button>
                                    <span class="bedroom-counter-value">1 Bedroom</span>
                                    <button type="button" class="bedroom-counter-btn" data-action="plus" aria-label="Increase bedrooms">+</button>
                                </div>
                                <input type="hidden" name="bedroom" value="{{ old('bedroom', 1) }}" id="bedroomHidden">
                            </div>
                        </div>

                        <div class="estimate-step-nav">
                            <div></div>
                            <button type="button" class="btn btn-primary" data-next-step>
                                Next: Your Details →
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Contact --}}
                    <div class="estimate-step">
                        <h2>How should we<br>reach you?</h2>
                        <p>We'll send your personalised estimate within 24 hours.</p>

                        <div class="estimate-fields">
                            <div class="form-group">
                                <label class="form-label" for="estName">Full name</label>
                                <input id="estName"
                                       class="form-input"
                                       type="text"
                                       name="name"
                                       placeholder="Your full name"
                                       value="{{ old('name') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="estEmail">Email address</label>
                                <input id="estEmail"
                                       class="form-input"
                                       type="email"
                                       name="email"
                                       placeholder="you@example.com"
                                       value="{{ old('email') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="estPhone">Mobile number</label>
                                <input id="estPhone"
                                       class="form-input"
                                       type="tel"
                                       name="phone"
                                       placeholder="+60 12 345 6789"
                                       value="{{ old('phone') }}"
                                       required>
                            </div>

                            <div style="display:flex; align-items:flex-start; gap:var(--space-3); font-size:var(--text-sm); color:var(--gray-500);">
                                <input type="checkbox" id="privacyCheck" required style="width:16px;height:16px;accent-color:var(--orange);flex-shrink:0;margin-top:2px;">
                                <label for="privacyCheck">
                                    By submitting, I accept MOKA's
                                    <a href="{{ url('/policy') }}" style="color:var(--orange);">privacy policy</a>.
                                </label>
                            </div>
                        </div>

                        <div class="estimate-step-nav">
                            <button type="button" class="btn btn-outline-teal btn-sm" data-prev-step>← Back</button>
                            <button type="submit" class="btn btn-primary">
                                Get My Estimate →
                            </button>
                        </div>
                    </div>

                    {{-- Step 3: Success (shown after server redirect with session) --}}
                    @if(session('success'))
                    <div class="estimate-step active">
                        <div class="estimate-success">
                            <div class="estimate-success-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <h2>You're all set!</h2>
                            <p>{{ session('success') }}</p>
                            <a href="{{ url('/homepage') }}" class="btn btn-teal">Back to Home</a>
                        </div>
                    </div>
                    @endif

                </form>

                {{-- WhatsApp alternative --}}
                <div style="text-align:center; margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--gray-100);">
                    <p style="font-size:var(--text-sm); color:var(--gray-400); margin-bottom:var(--space-4);">Prefer to talk directly?</p>
                    <a href="https://wa.me/message/GJMYMABOT7CSG1"
                       target="_blank" rel="noopener"
                       class="whatsapp-btn"
                       style="display:inline-flex; max-width:280px;">
                        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.119.553 4.107 1.522 5.834L0 24l6.335-1.502A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.667-.493-5.209-1.355l-.374-.222-3.755.89.949-3.658-.244-.389A9.939 9.939 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        Book a Free Consultation
                    </a>
                </div>

            </div>

        </div>
    </div>
</div>

@endsection

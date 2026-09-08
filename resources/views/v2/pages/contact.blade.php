{{-- Contact page on the homepage template, blog styling. Route: /contact --}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Contact MOKA | Airbnb & Short-Stay Property Management Malaysia')
@section('seo_description', 'Talk to MOKA about managing your property, a corporate stay or a partnership. Menara Lien Hoe, Tropicana, Petaling Jaya. We reply within one working day.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/solutions23.css') }}">
    <style>
    .ct-body{background:#fff;padding:56px 20px 72px}
    .ct-inner{max-width:1120px;margin:0 auto;display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:56px;align-items:start;color:var(--blog-ink)}
    .ct-form label{display:block;font-family:SemiBold;font-size:15.5px;color:var(--blog-teal);margin:0 0 6px}
    .ct-form input,.ct-form textarea{width:100%;border:1px solid var(--blog-rule);border-radius:12px;padding:13px 16px;font-size:16.5px;font-family:SansPro_Regular,sans-serif;color:var(--blog-ink);background:#fff;margin-bottom:18px;outline:0}
    .ct-form input:focus,.ct-form textarea:focus{border-color:var(--blog-orange);box-shadow:0 0 0 3px rgba(255,107,53,.15)}
    .ct-form textarea{min-height:160px;resize:vertical}
    .ct-form .err{color:#b91c1c;font-size:14px;margin:-12px 0 14px}
    .ct-form button{background:var(--blog-orange);color:#fff;border:0;border-radius:999px;padding:14px 32px;font-family:Bold;font-size:18px;cursor:pointer}
    .ct-form button:hover{background:#e55a28}
    .ct-ok{background:#e8f4f3;border-left:4px solid var(--blog-teal);padding:14px 18px;border-radius:0 10px 10px 0;margin-bottom:22px;font-size:16px;color:var(--blog-teal)}
    .ct-side .sol-card{margin-bottom:16px}
    .ct-side p{font-size:16.5px;line-height:1.6;color:#33403f;margin:0 0 6px}
    .ct-side .sol-card--teal h3{color:#fff}
    .ct-side .sol-card--teal p{color:rgba(255,255,255,.88);margin-bottom:16px}
    .ct-side a{color:var(--blog-teal);font-family:SemiBold;text-decoration:none}
    .ct-side a:hover{color:var(--blog-orange)}
    .ct-side .k{font-family:SemiBold;font-size:13px;letter-spacing:.1em;text-transform:uppercase;color:var(--blog-orange);margin:0 0 6px}
    @media (max-width:900px){.ct-inner{grid-template-columns:1fr;gap:36px}}
    </style>
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Contact</div>
            <h1>Talk to us</h1>
            <p class="blog-hero__meta">Managing a unit, housing a team, or introducing a client. Write to us and we reply within one working day.</p>
        </div>
    </div>

    <div class="ct-body">
        <div class="ct-inner">
            <div>
                @if(session('success'))<div class="ct-ok">{{ session('success') }}</div>@endif
                <form method="POST" action="/contact" class="ct-form">
                    @csrf
                    <label for="c-name">Name</label>
                    <input id="c-name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="err">{{ $message }}</div>@enderror
                    <label for="c-email">Email</label>
                    <input id="c-email" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="err">{{ $message }}</div>@enderror
                    <label for="c-phone">Phone</label>
                    <input id="c-phone" type="tel" name="phone" value="{{ old('phone') }}" required>
                    @error('phone')<div class="err">{{ $message }}</div>@enderror
                    <label for="c-message">Message</label>
                    <textarea id="c-message" name="message" required>{{ old('message') }}</textarea>
                    @error('message')<div class="err">{{ $message }}</div>@enderror
                    <button type="submit">Send message</button>
                </form>
            </div>
            <aside class="ct-side">
                <div class="sol-card">
                    <p class="k">Office</p>
                    <p>Menara Lien Hoe, Tropicana<br>47410 Petaling Jaya, Selangor</p>
                </div>
                <div class="sol-card">
                    <p class="k">Reach us</p>
                    <p><a href="tel:60367892288">+603 6789 2288</a></p>
                    <p><a href="mailto:hello@homemoka.com">hello@homemoka.com</a></p>
                    <p><a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">Chat on WhatsApp</a></p>
                    <p><a href="https://share.google/pLkG6DaCGgnjUA4mj" target="_blank" rel="noopener">Find us on Google</a></p>
                </div>
                <div class="sol-card sol-card--teal">
                    <h3>Own a unit?</h3>
                    <p>Get a free income estimate based on real bookings in comparable units.</p>
                    <a href="/get/estimate" target="_blank" rel="noopener" class="blog-cta__btn">Get a free estimate</a>
                </div>
            </aside>
        </div>
    </div>
@endsection

{{-- Careers page on the homepage template, blog styling. Route: /careers. Roles come from config/careers.php --}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Careers at MOKA | Jobs in Short-Stay Property Management')
@section('seo_description', 'Work with MOKA, the short-stay property management company in Kuala Lumpur and Selangor. See open roles in operations, guest sales, marketing and finance, and apply online.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/solutions23.css') }}">
    <style>
    .cr-body{background:#fff;padding:56px 20px 72px;color:var(--blog-ink)}
    .cr-inner{max-width:1120px;margin:0 auto}
    .cr-inner h2{font-family:Bold;font-size:28px;color:var(--blog-teal);margin:0 0 10px}
    .cr-lead{font-size:17.5px;line-height:1.65;color:#33403f;max-width:68ch;margin:0 0 28px}
    .cr-teams{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin:0 0 56px}
    .cr-teams .sol-card{margin:0}
    .cr-teams h3{font-family:SemiBold;font-size:18px;color:var(--blog-teal);margin:0 0 6px}
    .cr-teams p{font-size:15.5px;line-height:1.55;color:#33403f;margin:0}
    .cr-roles{display:grid;gap:16px;margin:0 0 56px}
    .cr-role{border:1px solid var(--blog-rule);border-radius:16px;padding:22px 24px}
    .cr-role h3{font-family:Bold;font-size:21px;color:var(--blog-ink);margin:0 0 4px}
    .cr-role .meta{font-family:SemiBold;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:var(--blog-orange);margin:0 0 10px}
    .cr-role p{font-size:16.5px;line-height:1.6;color:#33403f;margin:0 0 10px}
    .cr-role ul{margin:0 0 14px;padding-left:20px;font-size:16px;line-height:1.6;color:#33403f}
    .cr-role a.go{display:inline-block;background:var(--blog-teal);color:#fff;border-radius:999px;padding:10px 22px;font-family:SemiBold;font-size:15.5px;text-decoration:none}
    .cr-role a.go:hover{background:var(--blog-orange)}
    .cr-none{background:#f5f8f8;border-radius:16px;padding:20px 24px;font-size:16.5px;line-height:1.6;color:#33403f;margin:0 0 56px}
    .cr-apply{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:56px;align-items:start}
    .cr-form label{display:block;font-family:SemiBold;font-size:15.5px;color:var(--blog-teal);margin:0 0 6px}
    .cr-form label small{font-family:SansPro_Regular,sans-serif;font-size:14px;color:#6b7775}
    .cr-form input[type=text],.cr-form input[type=email],.cr-form input[type=tel],.cr-form input[type=url],.cr-form select,.cr-form textarea{width:100%;border:1px solid var(--blog-rule);border-radius:12px;padding:13px 16px;font-size:16.5px;font-family:SansPro_Regular,sans-serif;color:var(--blog-ink);background:#fff;margin:0 0 18px;box-sizing:border-box}
    .cr-form input[type=file]{width:100%;font-size:15.5px;margin:0 0 6px}
    .cr-form .hint{font-size:14px;color:#6b7775;margin:0 0 18px}
    .cr-form input:focus,.cr-form select:focus,.cr-form textarea:focus{outline:none;border-color:var(--blog-orange);box-shadow:0 0 0 3px rgba(255,107,53,.15)}
    .cr-form textarea{min-height:150px;resize:vertical}
    .cr-form .err{color:#b91c1c;font-size:14px;margin:-12px 0 14px}
    .cr-form .agree{display:flex;gap:10px;align-items:flex-start;font-family:SansPro_Regular,sans-serif;font-size:15px;line-height:1.5;color:#33403f;margin:0 0 20px}
    .cr-form .agree input{margin-top:4px;flex:none}
    .cr-form .agree a{color:var(--blog-teal)}
    .cr-form button{background:var(--blog-orange);color:#fff;border:0;border-radius:999px;padding:14px 32px;font-family:Bold;font-size:18px;cursor:pointer}
    .cr-form button:hover{background:#e55a28}
    .cr-ok{background:#e8f4f3;border-left:4px solid var(--blog-teal);padding:14px 18px;border-radius:0 10px 10px 0;margin-bottom:22px;font-size:16px;color:var(--blog-teal)}
    .cr-side .sol-card{margin-bottom:16px}
    .cr-side p{font-size:16.5px;line-height:1.6;color:#33403f;margin:0 0 6px}
    .cr-side .k{font-family:SemiBold;font-size:13px;letter-spacing:.1em;text-transform:uppercase;color:var(--blog-orange);margin:0 0 6px}
    .cr-side ol{margin:0;padding-left:20px;font-size:16px;line-height:1.6;color:#33403f}
    @media (max-width:900px){.cr-apply{grid-template-columns:1fr;gap:36px}}
    </style>
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Careers</div>
            <h1>Work with MOKA</h1>
            <p class="blog-hero__meta">We run homes like hotels for their owners. If you care about guests, clean rooms and getting the details right, we would like to hear from you.</p>
        </div>
    </div>

    <div class="cr-body">
        <div class="cr-inner">
            <h2>Where you could work</h2>
            <p class="cr-lead">MOKA has managed short-stay, monthly and corporate rentals since 2018, from an office in Petaling Jaya and on site in the buildings we run. The work falls into five teams.</p>
            <div class="cr-teams">
                <div class="sol-card"><h3>Operations</h3><p>Housekeeping, maintenance and keeping every unit ready before the guest arrives.</p></div>
                <div class="sol-card"><h3>Guest sales and front desk</h3><p>Reservations, pricing, check-ins and looking after guests during their stay.</p></div>
                <div class="sol-card"><h3>Business development</h3><p>Meeting owners, agents and developers, and bringing new units to MOKA.</p></div>
                <div class="sol-card"><h3>Marketing</h3><p>Our website, listings, content and the campaigns that bring direct bookings.</p></div>
                <div class="sol-card"><h3>Finance and accounts</h3><p>Owner reports, payments and the month-end numbers owners rely on.</p></div>
            </div>

            <h2>Open roles</h2>
            @if(empty($roles))
                <div class="cr-none">No specific role is listed right now. Send a general application below and tell us the kind of work you are looking for.</div>
            @else
                <div class="cr-roles">
                    @foreach($roles as $role)
                        <div class="cr-role" id="role-{{ $role['slug'] }}">
                            <h3>{{ $role['title'] }}</h3>
                            <p class="meta">{{ implode(' · ', array_filter([$role['type'] ?? null, $role['location'] ?? null])) }}</p>
                            @if(!empty($role['summary']))<p>{{ $role['summary'] }}</p>@endif
                            @if(!empty($role['points']))<ul>@foreach($role['points'] as $point)<li>{{ $point }}</li>@endforeach</ul>@endif
                            <a class="go" href="/careers?role={{ $role['slug'] }}#apply">Apply for this role</a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="cr-apply" id="apply">
                <div>
                    <h2>Apply</h2>
                    @if(session('success'))<div class="cr-ok">{{ session('success') }}</div>@endif
                    @php $picked = old('role', request('role', 'general')); @endphp
                    <form method="POST" action="/careers" class="cr-form" enctype="multipart/form-data">
                        @csrf
                        <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                        <input type="hidden" name="ft" value="{{ \App\Support\FormGuard::token() }}">

                        <label for="cr-role">Role</label>
                        <select id="cr-role" name="role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role['slug'] }}" @selected($picked === $role['slug'])>{{ $role['title'] }}</option>
                            @endforeach
                            <option value="general" @selected($picked === 'general' || !collect($roles)->pluck('slug')->contains($picked))>General application</option>
                        </select>
                        @error('role')<div class="err">{{ $message }}</div>@enderror

                        <label for="cr-name">Name</label>
                        <input id="cr-name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
                        @error('name')<div class="err">{{ $message }}</div>@enderror

                        <label for="cr-email">Email</label>
                        <input id="cr-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                        @error('email')<div class="err">{{ $message }}</div>@enderror

                        <label for="cr-phone">Phone</label>
                        <input id="cr-phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
                        @error('phone')<div class="err">{{ $message }}</div>@enderror

                        <label for="cr-link">LinkedIn or portfolio link <small>(optional)</small></label>
                        <input id="cr-link" type="url" name="link" value="{{ old('link') }}" placeholder="https://">
                        @error('link')<div class="err">{{ $message }}</div>@enderror

                        <label for="cr-message">About you</label>
                        <textarea id="cr-message" name="message" required placeholder="Your experience, the work you are looking for, and when you can start.">{{ old('message') }}</textarea>
                        @error('message')<div class="err">{{ $message }}</div>@enderror

                        <label for="cr-cv">CV <small>(optional)</small></label>
                        <input id="cr-cv" type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        <p class="hint">PDF or Word, 5 MB or smaller.</p>
                        @error('cv')<div class="err" style="margin-top:-12px">{{ $message }}</div>@enderror

                        <label class="agree"><input type="checkbox" name="agree" value="1" @checked(old('agree')) required><span>I agree that MOKA may keep the details and CV I send here to consider me for work. See our <a href="/policy" target="_blank" rel="noopener">privacy policy</a>.</span></label>
                        @error('agree')<div class="err">{{ $message }}</div>@enderror

                        <button type="submit">Send application</button>
                    </form>
                </div>
                <aside class="cr-side">
                    <div class="sol-card">
                        <p class="k">What happens next</p>
                        <ol>
                            <li>Your application goes straight to our team.</li>
                            <li>If there is a match, we contact you by phone or email.</li>
                            <li>We meet, in person or online.</li>
                        </ol>
                    </div>
                    <div class="sol-card">
                        <p class="k">Where we are</p>
                        <p>Office: Menara Lien Hoe, Tropicana, 47410 Petaling Jaya, Selangor.</p>
                        <p>On site: the buildings we manage in Kuala Lumpur and Selangor.</p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection

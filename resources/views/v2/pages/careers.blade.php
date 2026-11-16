{{-- Careers page on the homepage template, blog styling. Route: /careers. Roles come from config/careers.php --}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Careers at MOKA | Jobs in Short-Stay Property Management')
@section('seo_description', 'Work with MOKA, the short-stay property management company based at EkoCheras, Kuala Lumpur. See the open roles and apply online in a few minutes.')

@php
    $roleSlugs = collect($roles)->pluck('slug');
    $picked    = old('role', request('role', 'general'));
    $picked    = $roleSlugs->contains($picked) ? $picked : 'general';
    $sent      = session('success');
    $teams = [
        ['Operations', 'Housekeeping, maintenance and keeping every unit ready before the guest arrives.', 'M3 12l9-8 9 8M5 10v10h14V10'],
        ['Guest sales and front desk', 'Reservations, pricing, check-ins and looking after guests during their stay.', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
        ['Business development', 'Meeting owners, agents and developers, and bringing new units to MOKA.', 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8zm11 10v-2a4 4 0 00-3-3.9M15 2.1a4 4 0 010 7.8'],
        ['Marketing', 'Our website, listings, content and the campaigns that bring direct bookings.', 'M3 11v2a1 1 0 001 1h2l5 4V6L6 10H4a1 1 0 00-1 1zm13-3a5 5 0 010 8m3-11a9 9 0 010 14'],
        ['Finance and accounts', 'Owner reports, payments and the month-end numbers owners rely on.', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
    ];
@endphp

@push('schema')
    @php
        $crumbs = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Careers', 'item' => url('/careers')],
        ]];
        // A role is offered to job search engines only once it carries a 'posted' date.
        $postings = collect($roles)->filter(fn ($r) => !empty($r['posted']))->map(fn ($r) => [
            '@context' => 'https://schema.org', '@type' => 'JobPosting',
            'title' => $r['title'],
            'description' => '<p>' . e($r['summary'] ?? $r['title']) . '</p>' . (!empty($r['points']) ? '<ul>' . collect($r['points'])->map(fn ($p) => '<li>' . e($p) . '</li>')->implode('') . '</ul>' : ''),
            'datePosted' => $r['posted'],
            'employmentType' => $r['employment'] ?? 'FULL_TIME',
            'directApply' => true,
            'url' => url('/careers') . '#role-' . $r['slug'],
            'hiringOrganization' => ['@type' => 'Organization', '@id' => url('/') . '/#organization', 'name' => 'MOKA', 'sameAs' => url('/')],
            'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $r['city'] ?? 'Kuala Lumpur', 'addressRegion' => $r['region'] ?? ($r['city'] ?? 'Kuala Lumpur'), 'addressCountry' => 'MY']],
        ] + (!empty($r['closes']) ? ['validThrough' => $r['closes']] : []))->values();
    @endphp
    <script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @foreach($postings as $posting)
    <script type="application/ld+json">{!! json_encode($posting, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/solutions23.css') }}">
    <style>
    .cr-hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}
    .cr-btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 26px;border-radius:999px;font-family:Bold;font-size:17px;text-decoration:none;border:2px solid transparent;cursor:pointer;transition:background .15s,color .15s,border-color .15s}
    .cr-btn--go{background:var(--blog-orange);color:#fff}.cr-btn--go:hover{background:#e55a28;color:#fff}
    .cr-btn--line{border-color:rgba(255,255,255,.55);color:#fff}.cr-btn--line:hover{border-color:#fff;background:rgba(255,255,255,.1);color:#fff}
    .cr-btn--teal{background:var(--blog-teal);color:#fff}.cr-btn--teal:hover{background:var(--blog-orange);color:#fff}
    .cr-btn:focus-visible,.cr-form :focus-visible,.cr-role summary:focus-visible{outline:3px solid rgba(255,107,53,.55);outline-offset:2px}
    .cr-body{background:#fff;padding:64px 20px 80px;color:var(--blog-ink)}
    .cr-body,.cr-body p,.cr-body li,.blog-hero__meta{text-align:left}
    .cr-inner{max-width:1120px;margin:0 auto}
    .cr-sec{margin:0 0 64px;scroll-margin-top:96px}
    .cr-sec h2{font-family:Bold;font-size:clamp(24px,3.2vw,30px);line-height:1.2;color:var(--blog-teal);margin:0 0 10px}
    .cr-lead{font-size:17.5px;line-height:1.65;color:#33403f;max-width:66ch;margin:0 0 26px}
    .cr-teams{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;list-style:none;margin:0;padding:0}
    .cr-team{border:1px solid var(--blog-rule);border-radius:16px;padding:20px 18px;background:#fff}
    .cr-team svg{width:28px;height:28px;stroke:var(--blog-orange);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;margin:0 0 12px;display:block}
    .cr-team h3{font-family:SemiBold;font-size:17px;line-height:1.3;color:var(--blog-teal);margin:0 0 6px}
    .cr-team p{font-size:15px;line-height:1.55;color:#4a5856;margin:0}
    .cr-roles{display:grid;gap:14px}
    .cr-role{border:1px solid var(--blog-rule);border-radius:16px;background:#fff;transition:border-color .15s,box-shadow .15s}
    .cr-role[open],.cr-role:hover{border-color:#c9d6d4;box-shadow:0 6px 22px rgba(0,61,60,.07)}
    .cr-role summary{list-style:none;cursor:pointer;padding:20px 56px 20px 24px;position:relative;border-radius:16px}
    .cr-role summary::-webkit-details-marker{display:none}
    .cr-role summary::after{content:"";position:absolute;right:24px;top:28px;width:10px;height:10px;border-right:2px solid var(--blog-teal);border-bottom:2px solid var(--blog-teal);transform:rotate(45deg);transition:transform .15s}
    .cr-role[open] summary::after{transform:rotate(225deg);top:33px}
    .cr-role h3{font-family:Bold;font-size:20px;line-height:1.3;color:var(--blog-ink);margin:0 0 6px}
    .cr-role .meta{display:flex;flex-wrap:wrap;gap:8px;margin:0}
    .cr-role .meta span{font-family:SemiBold;font-size:12.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--blog-teal);background:#e8f4f3;border-radius:999px;padding:5px 11px}
    .cr-role .more{padding:0 24px 22px}
    .cr-role .more p{font-size:16.5px;line-height:1.6;color:#33403f;margin:0 0 10px}
    .cr-role .more ul{margin:0 0 16px;padding-left:20px;font-size:16px;line-height:1.6;color:#33403f}
    .cr-role--plain{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 24px;padding:20px 24px}
    .cr-role--plain .txt{flex:1 1 380px;min-width:0}
    .cr-role--plain .sum{font-size:16.5px;line-height:1.55;color:#33403f;margin:10px 0 0}
    .cr-role--plain .cr-btn{min-width:120px}
    .cr-none{background:#f4f8f7;border-radius:16px;padding:22px 24px;display:flex;flex-wrap:wrap;gap:14px 24px;align-items:center;justify-content:space-between}
    .cr-none p{font-size:16.5px;line-height:1.6;color:#33403f;margin:0;flex:1 1 320px}
    .cr-apply{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(0,1fr);gap:56px;align-items:start}
    .cr-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px}
    .cr-f{min-width:0}.cr-f--wide{grid-column:1/-1}
    .cr-form label.lb{display:block;font-family:SemiBold;font-size:15.5px;color:var(--blog-teal);margin:0 0 6px}
    .cr-form label.lb small{font-family:SansPro_Regular,sans-serif;font-size:14px;color:#6b7775}
    .cr-form input[type=text],.cr-form input[type=email],.cr-form input[type=tel],.cr-form input[type=url],.cr-form select,.cr-form textarea{width:100%;min-height:50px;border:1px solid #cfd8d6;border-radius:12px;padding:12px 16px;font-size:16.5px;font-family:SansPro_Regular,sans-serif;color:var(--blog-ink);background:#fff;margin:0;box-sizing:border-box;-webkit-appearance:none;appearance:none}
    .cr-form select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23003d3c' stroke-width='2'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;background-size:12px;padding-right:42px}
    .cr-form input:focus,.cr-form select:focus,.cr-form textarea:focus{outline:none;border-color:var(--blog-orange);box-shadow:0 0 0 3px rgba(255,107,53,.15)}
    .cr-form [aria-invalid=true]{border-color:#b91c1c}
    .cr-form textarea{min-height:150px;resize:vertical;display:block}
    .cr-form .hint{font-size:14px;color:#6b7775;margin:6px 0 0;display:flex;justify-content:space-between;gap:12px}
    .cr-form .err{color:#b91c1c;font-size:14.5px;margin:6px 0 0}
    .cr-file{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;border:1.5px dashed #b9c7c5;border-radius:12px;padding:14px 16px;background:#fafcfb}
    .cr-file input{position:absolute;width:1px;height:1px;opacity:0;overflow:hidden}
    .cr-file label.pick{display:inline-flex;align-items:center;min-height:44px;padding:0 18px;border-radius:999px;border:1.5px solid var(--blog-teal);color:var(--blog-teal);font-family:SemiBold;font-size:15.5px;cursor:pointer;background:#fff;margin:0}
    .cr-file label.pick:hover{background:var(--blog-teal);color:#fff}
    .cr-file input:focus-visible + label.pick{outline:3px solid rgba(255,107,53,.55);outline-offset:2px}
    .cr-file .name{font-size:15px;color:#33403f;word-break:break-all;flex:1 1 160px}
    .cr-file button.clear{background:none;border:0;padding:8px;color:#b91c1c;font-family:SemiBold;font-size:14.5px;cursor:pointer;min-height:44px}
    .cr-agree{display:flex;gap:12px;align-items:flex-start;font-family:SansPro_Regular,sans-serif;font-size:15.5px;line-height:1.5;color:#33403f;margin:0;cursor:pointer}
    .cr-agree input{width:22px;height:22px;margin:1px 0 0;flex:none;accent-color:var(--blog-teal)}
    .cr-agree a{color:var(--blog-teal);text-decoration:underline}
    .cr-send{display:flex;flex-wrap:wrap;align-items:center;gap:12px 18px}
    .cr-send .cr-btn[disabled]{opacity:.65;cursor:progress}
    .cr-send span{font-size:14.5px;color:#6b7775}
    .cr-alert{grid-column:1/-1;background:#fdf1f0;border-left:4px solid #b91c1c;border-radius:0 10px 10px 0;padding:12px 16px;font-size:15.5px;line-height:1.5;color:#7f1d1d}
    .cr-done{background:#e8f4f3;border-radius:16px;padding:28px 26px;color:var(--blog-teal)}
    .cr-done svg{width:40px;height:40px;stroke:var(--blog-teal);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;display:block;margin:0 0 12px}
    .cr-done h3{font-family:Bold;font-size:22px;margin:0 0 8px;color:var(--blog-teal)}
    .cr-done p{font-size:16.5px;line-height:1.6;margin:0 0 16px;color:#1d2b2a}
    .cr-done a{color:var(--blog-teal);font-family:SemiBold}
    .cr-side{display:grid;gap:16px;position:sticky;top:104px}
    .cr-card{border:1px solid var(--blog-rule);border-radius:16px;padding:22px 24px;background:#fff}
    .cr-card .k{font-family:SemiBold;font-size:13px;letter-spacing:.1em;text-transform:uppercase;color:var(--blog-orange);margin:0 0 10px}
    .cr-card p{font-size:16px;line-height:1.6;color:#33403f;margin:0 0 6px}
    .cr-card ol{margin:0;padding:0;list-style:none;counter-reset:s;display:grid;gap:12px}
    .cr-card ol li{counter-increment:s;position:relative;padding-left:40px;font-size:16px;line-height:1.5;color:#33403f}
    .cr-card ol li::before{content:counter(s);position:absolute;left:0;top:-1px;width:28px;height:28px;border-radius:50%;background:var(--blog-teal);color:#fff;font-family:Bold;font-size:14px;display:flex;align-items:center;justify-content:center}
    .cr-card a{color:var(--blog-teal);font-family:SemiBold;text-decoration:none}.cr-card a:hover{color:var(--blog-orange)}
    .cr-bar{display:none}
    @media (max-width:1000px){.cr-teams{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media (max-width:900px){.cr-apply{grid-template-columns:1fr;gap:32px}.cr-side{position:static}}
    @media (max-width:640px){
        .cr-body{padding:44px 16px 96px}.cr-sec{margin-bottom:48px}
        .cr-teams{grid-template-columns:1fr;gap:10px}
        .cr-team{display:grid;grid-template-columns:28px 1fr;column-gap:14px;padding:16px}.cr-team svg{grid-row:1/3;margin:2px 0 0}.cr-team h3{font-size:16.5px}
        .cr-form{grid-template-columns:1fr}
        .cr-hero-actions .cr-btn{flex:1 1 100%}
        .cr-role summary{padding:18px 48px 18px 18px}.cr-role summary::after{right:18px}.cr-role .more{padding:0 18px 20px}
        .cr-role--plain{padding:18px}.cr-role--plain .cr-btn{width:100%}
        .cr-send .cr-btn{width:100%}
        .cr-bar{display:block;position:fixed;left:0;right:0;bottom:0;z-index:40;padding:10px 16px calc(10px + env(safe-area-inset-bottom,0px));background:rgba(255,255,255,.96);border-top:1px solid var(--blog-rule);transform:translateY(110%);transition:transform .2s}
        .cr-bar.on{transform:none}.cr-bar .cr-btn{width:100%}
    }
    @media (prefers-reduced-motion:reduce){.cr-btn,.cr-role,.cr-role summary::after,.cr-bar{transition:none}}
    </style>
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Careers</div>
            <h1>Work with MOKA</h1>
            <p class="blog-hero__meta">We run homes like hotels for their owners. If you care about guests, clean rooms and getting the details right, we would like to hear from you.</p>
            <div class="cr-hero-actions">
                <a class="cr-btn cr-btn--go" href="#apply">Apply now</a>
                <a class="cr-btn cr-btn--line" href="#roles">See open roles</a>
            </div>
        </div>
    </div>

    <div class="cr-body">
        <div class="cr-inner">
            <section class="cr-sec" aria-labelledby="cr-h-teams">
                <h2 id="cr-h-teams">Where you could work</h2>
                <p class="cr-lead">MOKA has managed short-stay, monthly and corporate rentals since 2018, from our office at EkoCheras in Kuala Lumpur and on site in the buildings we run. The work falls into five teams.</p>
                <ul class="cr-teams">
                    @foreach($teams as [$name, $text, $icon])
                        <li class="cr-team"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg><h3>{{ $name }}</h3><p>{{ $text }}</p></li>
                    @endforeach
                </ul>
            </section>

            <section class="cr-sec" id="roles" aria-labelledby="cr-h-roles">
                <h2 id="cr-h-roles">Open roles</h2>
                @if(empty($roles))
                    <div class="cr-none">
                        <p>No specific role is listed right now. Send a general application and tell us the kind of work you are looking for.</p>
                        <a class="cr-btn cr-btn--teal" href="#apply">Send a general application</a>
                    </div>
                @else
                    <p class="cr-lead">{{ count($roles) }} {{ count($roles) === 1 ? 'role is' : 'roles are' }} open.{{ collect($roles)->contains(fn ($r) => !empty($r['points'])) ? ' Tap a role to read more.' : '' }}</p>
                    <div class="cr-roles">
                        @foreach($roles as $role)
                            @if(empty($role['points']))
                            {{-- A role with one line and no bullet points is shown in full, nothing to expand. --}}
                            <div class="cr-role cr-role--plain" id="role-{{ $role['slug'] }}">
                                <div class="txt">
                                    <h3>{{ $role['title'] }}</h3>
                                    <p class="meta">@foreach(array_filter([$role['type'] ?? null, $role['location'] ?? null]) as $tag)<span>{{ $tag }}</span>@endforeach</p>
                                    @if(!empty($role['summary']))<p class="sum">{{ $role['summary'] }}</p>@endif
                                </div>
                                <a class="cr-btn cr-btn--teal" href="#apply" data-role="{{ $role['slug'] }}">Apply</a>
                            </div>
                            @continue
                            @endif
                            <details class="cr-role" id="role-{{ $role['slug'] }}" @if($picked === $role['slug'] || count($roles) === 1) open @endif>
                                <summary>
                                    <h3>{{ $role['title'] }}</h3>
                                    <p class="meta">@foreach(array_filter([$role['type'] ?? null, $role['location'] ?? null]) as $tag)<span>{{ $tag }}</span>@endforeach</p>
                                </summary>
                                <div class="more">
                                    @if(!empty($role['summary']))<p>{{ $role['summary'] }}</p>@endif
                                    @if(!empty($role['points']))<ul>@foreach($role['points'] as $point)<li>{{ $point }}</li>@endforeach</ul>@endif
                                    <a class="cr-btn cr-btn--teal" href="#apply" data-role="{{ $role['slug'] }}">Apply for this role</a>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="cr-sec cr-apply" id="apply" aria-labelledby="cr-h-apply" style="margin-bottom:0">
                <div>
                    <h2 id="cr-h-apply">Apply</h2>
                    @if($sent)
                        <div class="cr-done" id="cr-done" role="status" tabindex="-1">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/></svg>
                            <h3>Application received</h3>
                            <p>{{ $sent }}</p>
                            <a href="/careers#apply">Send another application</a>
                        </div>
                    @else
                    <p class="cr-lead" style="margin-bottom:22px">It takes about three minutes. Fields marked optional can be left empty.</p>
                    <form method="POST" action="/careers" class="cr-form" id="cr-form" enctype="multipart/form-data" novalidate>
                        @csrf
                        <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                        <input type="hidden" name="ft" value="{{ \App\Support\FormGuard::token() }}">

                        @if($errors->any())
                            <div class="cr-alert" role="alert" id="cr-alert" tabindex="-1">Please check the fields marked in red. If you attached a CV, attach it again before sending.</div>
                        @endif

                        <div class="cr-f cr-f--wide">
                            <label class="lb" for="cr-role">Role</label>
                            <select id="cr-role" name="role" required @error('role') aria-invalid="true" aria-describedby="cr-role-e" @enderror>
                                @foreach($roles as $role)
                                    <option value="{{ $role['slug'] }}" @selected($picked === $role['slug'])>{{ $role['title'] }}</option>
                                @endforeach
                                <option value="general" @selected($picked === 'general')>General application</option>
                            </select>
                            @error('role')<p class="err" id="cr-role-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f cr-f--wide">
                            <label class="lb" for="cr-name">Full name</label>
                            <input id="cr-name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required @error('name') aria-invalid="true" aria-describedby="cr-name-e" @enderror>
                            @error('name')<p class="err" id="cr-name-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f">
                            <label class="lb" for="cr-email">Email</label>
                            <input id="cr-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" maxlength="150" required @error('email') aria-invalid="true" aria-describedby="cr-email-e" @enderror>
                            @error('email')<p class="err" id="cr-email-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f">
                            <label class="lb" for="cr-phone">Phone</label>
                            <input id="cr-phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" maxlength="40" required @error('phone') aria-invalid="true" aria-describedby="cr-phone-e" @enderror>
                            @error('phone')<p class="err" id="cr-phone-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f cr-f--wide">
                            <label class="lb" for="cr-message">About you</label>
                            <textarea id="cr-message" name="message" maxlength="4000" required placeholder="Your experience, the work you are looking for, and when you can start." @error('message') aria-invalid="true" aria-describedby="cr-message-e" @enderror>{{ old('message') }}</textarea>
                            <p class="hint"><span>A few sentences is enough.</span><span id="cr-count" aria-hidden="true"></span></p>
                            @error('message')<p class="err" id="cr-message-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f cr-f--wide">
                            <span class="lb" id="cr-cv-l" style="display:block;font-family:SemiBold;font-size:15.5px;color:var(--blog-teal);margin:0 0 6px">CV <small style="font-family:SansPro_Regular,sans-serif;font-size:14px;color:#6b7775">(optional)</small></span>
                            <div class="cr-file">
                                <input id="cr-cv" type="file" name="cv" aria-labelledby="cr-cv-l" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                <label class="pick" for="cr-cv">Choose a file</label>
                                <span class="name" id="cr-cv-name">PDF or Word, 5 MB or smaller.</span>
                                <button type="button" class="clear" id="cr-cv-clear" hidden>Remove</button>
                            </div>
                            <p class="err" id="cr-cv-e" @if(!$errors->has('cv')) hidden @endif>{{ $errors->first('cv') }}</p>
                        </div>

                        <div class="cr-f cr-f--wide">
                            <label class="lb" for="cr-link">LinkedIn or portfolio link <small>(optional)</small></label>
                            <input id="cr-link" type="url" name="link" value="{{ old('link') }}" placeholder="https://" inputmode="url" maxlength="255" @error('link') aria-invalid="true" aria-describedby="cr-link-e" @enderror>
                            @error('link')<p class="err" id="cr-link-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f cr-f--wide">
                            <label class="cr-agree"><input type="checkbox" name="agree" value="1" @checked(old('agree')) required @error('agree') aria-invalid="true" aria-describedby="cr-agree-e" @enderror><span>I agree that MOKA may keep the details and CV I send here to consider me for work. See our <a href="/policy" target="_blank" rel="noopener">privacy policy</a>.</span></label>
                            @error('agree')<p class="err" id="cr-agree-e">{{ $message }}</p>@enderror
                        </div>

                        <div class="cr-f cr-f--wide cr-send">
                            <button type="submit" class="cr-btn cr-btn--go" id="cr-submit">Send application</button>
                            <span>We read every application.</span>
                        </div>
                    </form>
                    @endif
                </div>
                <aside class="cr-side" aria-label="About applying">
                    <div class="cr-card">
                        <p class="k">What happens next</p>
                        <ol>
                            <li>Your application goes straight to our team.</li>
                            <li>If there is a match, we contact you by phone or email.</li>
                            <li>We meet, in person or online.</li>
                        </ol>
                    </div>
                    <div class="cr-card">
                        <p class="k">Where we are</p>
                        <p>Office: EkoCheras, Jalan Cheras, 56000 Kuala Lumpur.</p>
                        <p>On site: the other buildings we manage in Kuala Lumpur and Selangor.</p>
                    </div>
                    <div class="cr-card">
                        <p class="k">A question first?</p>
                        <p><a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">Chat on WhatsApp</a></p>
                        <p><a href="/contact">Send us a message</a></p>
                    </div>
                </aside>
            </section>
        </div>
    </div>

    @unless($sent)
    <div class="cr-bar" id="cr-bar"><a class="cr-btn cr-btn--go" href="#apply">Apply now</a></div>
    @endunless
@endsection

@push('scripts')
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var done = $('cr-done'), alertBox = $('cr-alert'), form = $('cr-form');
    if (done) { done.focus({ preventScroll: true }); done.scrollIntoView({ block: 'center' }); }
    if (alertBox) { var bad = form.querySelector('[aria-invalid=true]'); (bad || alertBox).focus({ preventScroll: true }); alertBox.scrollIntoView({ block: 'start' }); }
    if (!form) { return; }

    // Role buttons choose the role in the form.
    document.querySelectorAll('[data-role]').forEach(function (a) {
        a.addEventListener('click', function () { $('cr-role').value = a.getAttribute('data-role'); });
    });

    // Message length.
    var msg = $('cr-message'), count = $('cr-count');
    var showCount = function () { count.textContent = msg.value.length > 3000 ? (4000 - msg.value.length) + ' characters left' : ''; };
    msg.addEventListener('input', showCount); showCount();

    // CV: show the chosen file, and refuse a wrong type or size before anything is uploaded.
    var cv = $('cr-cv'), cvName = $('cr-cv-name'), cvErr = $('cr-cv-e'), cvClear = $('cr-cv-clear'), empty = cvName.textContent;
    var resetCv = function () { cv.value = ''; cvName.textContent = empty; cvClear.hidden = true; };
    cv.addEventListener('change', function () {
        var f = cv.files && cv.files[0]; cvErr.hidden = true;
        if (!f) { resetCv(); return; }
        var okType = /\.(pdf|docx?)$/i.test(f.name), okSize = f.size <= 5 * 1024 * 1024;
        if (!okType || !okSize) {
            cvErr.textContent = okType ? 'The CV must be 5 MB or smaller.' : 'The CV must be a PDF or Word file.'; cvErr.hidden = false; resetCv(); return;
        }
        cvName.textContent = f.name + ' (' + (f.size / 1048576 < 0.1 ? Math.max(1, Math.round(f.size / 1024)) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB') + ')'; cvClear.hidden = false;
    });
    cvClear.addEventListener('click', function () { resetCv(); cv.focus(); });

    // One send only; the browser's own checks run first because the form is marked novalidate for our styling.
    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) { e.preventDefault(); form.reportValidity(); return; }
        var b = $('cr-submit'); if (b.disabled) { e.preventDefault(); return; }
        b.disabled = true; b.textContent = 'Sending…';
    });
    window.addEventListener('pageshow', function () { var b = $('cr-submit'); b.disabled = false; b.textContent = 'Send application'; });

    // Phone: the Apply bar shows between the top buttons and the form.
    var bar = $('cr-bar'), hero = document.querySelector('.cr-hero-actions');
    if (bar && hero && 'IntersectionObserver' in window) {
        var seen = { hero: true, form: false };
        var set = function () { bar.classList.toggle('on', !seen.hero && !seen.form); };
        new IntersectionObserver(function (en) { seen.hero = en[0].isIntersecting; set(); }).observe(hero);
        new IntersectionObserver(function (en) { seen.form = en[0].isIntersecting; set(); }, { rootMargin: '0px 0px -20% 0px' }).observe(form);
    }
})();
</script>
@endpush

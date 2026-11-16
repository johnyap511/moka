{{-- Careers page on the homepage template, blog styling. Route: /careers. Roles come from config/careers.php --}}
@extends('auth.newTheme.layout')
@section('seo_title', ! empty($focus) ? $focus['title'] . ' | Careers at MOKA' : 'Careers at MOKA | Jobs in Short-Stay Property Management')
@section('seo_description', ! empty($focus) ? (mb_strlen($focus['summary'] ?? '') > 110 ? $focus['summary'] : trim(($focus['summary'] ?? '') . ' Apply online for this role at MOKA, Kuala Lumpur.')) : 'Work with MOKA, the short-stay property management company based at EkoCheras, Kuala Lumpur. See the open roles and apply online in a few minutes.')

@php
    $roleSlugs = collect($roles)->pluck('slug');
    $focus     = $focus ?? null;
    $picked    = old('role', $focus['slug'] ?? request('role', 'general'));
    $picked    = $roleSlugs->contains($picked) ? $picked : 'general';
    $sent      = session('success');
    $roleCount = count($roles);
    // Teams as Sam set them on 16 Nov 2026: Operations covers front desk and revenue; HR is the fifth team.
    $teams = [
        ['Operations', 'Front desk, pricing and revenue, housekeeping and maintenance. Everything that keeps a stay running.', 'M3 12l9-8 9 8M5 10v10h14V10'],
        ['Business development', 'Meeting owners, agents and developers, and bringing new units to MOKA.', 'M3 17l6-6 4 4 8-8M15 7h6v6'],
        ['Marketing', 'Our website, listings, content and the campaigns that bring direct bookings.', 'M3 11v2a1 1 0 001 1h2l5 4V6L6 10H4a1 1 0 00-1 1zm13-3a5 5 0 010 8m3-11a9 9 0 010 14'],
        ['Finance and accounts', 'Owner reports, payments and the month-end numbers owners rely on.', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
        ['Human resources', 'Hiring, training and supporting the people who look after our guests.', 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10-3v6m3-3h-6'],
    ];
@endphp

@push('schema')
    @php
        $crumbs = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Careers', 'item' => url('/careers')],
        ]];
        if ($focus) { $crumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => 3, 'name' => $focus['title'], 'item' => url('/careers/' . $focus['slug'])]; }
        // A role is offered to job search engines only once it carries a 'posted' date.
        $postings = collect($focus ? [$focus] : [])->filter(fn ($r) => !empty($r['posted']))->map(fn ($r) => [
            '@context' => 'https://schema.org', '@type' => 'JobPosting',
            'title' => $r['title'],
            'description' => '<p>' . e($r['summary'] ?? $r['title']) . '</p>' . (!empty($r['points']) ? '<ul>' . collect($r['points'])->map(fn ($p) => '<li>' . e($p) . '</li>')->implode('') . '</ul>' : '') . '<p>' . e(implode(', ', array_filter([$r['type'] ?? null, $r['location'] ?? null]))) . '. Apply online at homemoka.com/careers.</p>',
            'datePosted' => $r['posted'],
            'employmentType' => $r['employment'] ?? 'FULL_TIME',
            'directApply' => true,
            'url' => url('/careers/' . $r['slug']),
            'identifier' => ['@type' => 'PropertyValue', 'name' => 'MOKA', 'value' => $r['slug']],
            'hiringOrganization' => ['@type' => 'Organization', '@id' => url('/') . '/#organization', 'name' => 'MOKA', 'sameAs' => url('/'), 'logo' => asset('new-theme23/images/logo.png')],
            'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'EkoCheras, Jalan Cheras', 'postalCode' => '56000', 'addressLocality' => $r['city'] ?? 'Kuala Lumpur', 'addressRegion' => $r['region'] ?? ($r['city'] ?? 'Kuala Lumpur'), 'addressCountry' => 'MY']],
        ] + (!empty($r['closes']) ? ['validThrough' => $r['closes']] : []))->values();
    @endphp
    <script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @foreach($postings as $posting)
    <script type="application/ld+json">{!! json_encode($posting, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <style>
    /* Careers. One brand system: teal, orange, cream; the roof shape from the logo frames the photo. */
    .cr{--teal:#003d3c;--orange:#ff6b35;--cream:#faf8f4;--ink:#1d2b2a;--muted:#5c6b6a;--rule:#e4e0d8;--mint:#e3f1ef;color:var(--ink);font-family:SansPro_Regular,sans-serif;background:#fff}
    .cr p,.cr li,.cr h1,.cr h2,.cr h3,.cr span,.cr label{text-align:left}
    .cr-wrap{max-width:1160px;margin:0 auto;padding:0 24px}
    .cr-eyebrow{font-family:SemiBold;font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:var(--orange);margin:0 0 16px}
    .cr h2{font-family:Bold;font-size:clamp(28px,3.6vw,42px);line-height:1.1;letter-spacing:-.01em;color:var(--teal);margin:0 0 14px}
    .cr-lead{font-size:18px;line-height:1.65;color:#3b4a48;max-width:36em;margin:0}
    .cr-sec{padding:88px 0;scroll-margin-top:84px}
    .cr-sec--cream{background:var(--cream)}
    .cr-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:18px 40px;margin:0 0 40px}
    .cr-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:0 28px;border-radius:999px;font-family:Bold;font-size:17.5px;line-height:1;text-decoration:none;border:2px solid transparent;cursor:pointer;transition:background .18s,color .18s,border-color .18s,transform .18s}
    .cr-btn svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round;transition:transform .18s}
    .cr-btn:hover svg{transform:translateX(3px)}
    .cr-btn--go{background:var(--orange);color:#fff}.cr-btn--go:hover{background:#e9591f;color:#fff}
    .cr-btn--line{border-color:rgba(255,255,255,.5);color:#fff}.cr-btn--line:hover{border-color:#fff;background:rgba(255,255,255,.08);color:#fff}
    .cr-btn:focus-visible,.cr-job:focus-visible,.cr-form :focus-visible{outline:3px solid rgba(255,107,53,.6);outline-offset:3px}

    /* Hero */
    .cr-hero{background:var(--teal);color:#fff;padding:150px 0 0;position:relative;overflow:hidden}
    .cr-hero__grid{position:relative;display:grid;grid-template-columns:minmax(0,1.22fr) minmax(0,.78fr);gap:56px;align-items:end}
    .cr-hero__copy{padding:18px 0 92px}
    .cr-hero h1{font-family:Bold;font-size:clamp(36px,4.5vw,58px);line-height:1.06;letter-spacing:-.02em;color:#fff;margin:0 0 22px;text-wrap:balance}
    .cr-hero h1 em{font-style:normal;color:var(--orange);display:block}
    .cr-hero__lead{font-size:clamp(17.5px,1.55vw,20.5px);line-height:1.6;color:rgba(255,255,255,.84);max-width:33em;margin:0 0 32px}
    .cr-actions{display:flex;flex-wrap:wrap;gap:12px}
    .cr-hero__tags{display:flex;flex-wrap:wrap;gap:8px;margin:-6px 0 20px}
    .cr-hero__tags span{font-family:SemiBold;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#fff;background:rgba(255,255,255,.14);border-radius:999px;padding:7px 14px}
    .cr-hero__pts{margin:-14px 0 30px;padding-left:20px;font-size:17px;line-height:1.6;color:rgba(255,255,255,.84)}
    .cr-hero__art{position:relative;padding-bottom:56px}
    .cr-hero__art img{display:block;width:100%;aspect-ratio:1/1.04;object-fit:cover;-webkit-clip-path:url(#cr-house);clip-path:url(#cr-house);background:#0d4f4d}
    .cr-chip{position:absolute;display:flex;align-items:center;gap:12px;background:#fff;color:var(--ink);border-radius:18px;padding:12px 18px 12px 12px;box-shadow:0 18px 40px rgba(0,0,0,.25)}
    .cr-chip i{flex:none;width:40px;height:40px;border-radius:12px;background:var(--mint);display:flex;align-items:center;justify-content:center}
    .cr-chip i svg{width:20px;height:20px;stroke:var(--teal);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
    .cr-chip b{display:block;font-family:Bold;font-size:17px;line-height:1.15;color:var(--teal)}
    .cr-chip small{display:block;font-size:13.5px;line-height:1.2;color:var(--muted)}
    .cr-chip--a{left:-28px;bottom:150px}.cr-chip--b{right:-8px;bottom:84px}

    /* Facts band */
    .cr-facts{background:var(--cream);border-bottom:1px solid var(--rule)}
    .cr-facts ul{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}
    .cr-facts li{padding:34px 28px;border-left:1px solid var(--rule)}
    .cr-facts li:first-child{border-left:0;padding-left:0}
    .cr-facts b{display:block;font-family:Bold;font-size:clamp(26px,2.8vw,36px);line-height:1.05;letter-spacing:-.01em;color:var(--teal)}
    .cr-facts span{display:block;font-size:15.5px;line-height:1.4;color:var(--muted);margin-top:6px}

    /* Teams */
    .cr-teams{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}
    .cr-team{background:var(--cream);border-radius:22px;padding:26px 22px 28px;transition:transform .2s,box-shadow .2s,background .2s}
    .cr-team:hover{transform:translateY(-4px);background:#fff;box-shadow:0 18px 40px rgba(0,61,60,.12)}
    .cr-team i{width:48px;height:48px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;margin:0 0 18px;box-shadow:0 2px 0 var(--rule)}
    .cr-team:hover i{background:var(--mint);box-shadow:none}
    .cr-team svg{width:24px;height:24px;stroke:var(--orange);fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
    .cr-team h3{font-family:Bold;font-size:18.5px;line-height:1.25;color:var(--teal);margin:0 0 8px}
    .cr-team p{font-size:15.5px;line-height:1.55;color:#4a5856;margin:0}

    /* Roles */
    .cr-count{font-family:SemiBold;font-size:14px;letter-spacing:.04em;color:var(--teal);background:var(--mint);border-radius:999px;padding:9px 16px;white-space:nowrap}
    .cr-jobs{background:#fff;border:1px solid var(--rule);border-radius:26px;overflow:hidden;box-shadow:0 20px 50px rgba(0,61,60,.06)}
    .cr-job{display:grid;grid-template-columns:54px minmax(0,1fr) auto 50px;gap:8px 22px;align-items:center;padding:26px 28px;color:inherit;text-decoration:none;border-top:1px solid var(--rule);transition:background .18s}
    .cr-job:first-child{border-top:0}
    .cr-job:hover{background:#fff8f4;color:inherit}
    .cr-job .no{font-family:SemiBold;font-size:15px;letter-spacing:.06em;color:var(--orange)}
    .cr-job h3{font-family:Bold;font-size:clamp(19px,1.9vw,23px);line-height:1.25;color:var(--ink);margin:0 0 4px}
    .cr-job .sum{font-size:16px;line-height:1.5;color:var(--muted);margin:0}
    .cr-job .pts{margin:8px 0 0;padding-left:18px;font-size:15.5px;line-height:1.55;color:var(--muted)}
    .cr-job .tags{display:flex;flex-wrap:nowrap;justify-content:flex-end;gap:8px}
    .cr-job .tags span{font-family:SemiBold;font-size:12.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--teal);background:var(--mint);border-radius:999px;padding:6px 12px;white-space:nowrap}
    .cr-job .tags span.t{background:#fff0e9;color:#c2410c}
    .cr-job .go{width:50px;height:50px;border-radius:50%;border:1.5px solid var(--rule);display:flex;align-items:center;justify-content:center;transition:background .18s,border-color .18s,transform .18s}
    .cr-job .go svg{width:20px;height:20px;stroke:var(--teal);fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round;transition:stroke .18s}
    .cr-job:hover .go{background:var(--orange);border-color:var(--orange);transform:translateX(4px)}
    .cr-job:hover .go svg{stroke:#fff}
    .cr-job--open{background:var(--cream)}
    .cr-job--open .no svg{width:22px;height:22px;stroke:var(--orange);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;display:block}

    /* Steps */
    .cr-steps{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px;counter-reset:st;position:relative}
    .cr-steps li:not(:last-child)::after{content:"";position:absolute;left:72px;right:-12px;top:27px;border-top:2px dashed #cfdad8}
    .cr-steps li{counter-increment:st;position:relative;padding-top:78px}
    .cr-steps li::before{content:counter(st);position:absolute;left:0;top:0;width:56px;height:56px;border-radius:50%;background:var(--teal);color:#fff;font-family:Bold;font-size:22px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 8px #fff}
    .cr-steps h3{font-family:Bold;font-size:20px;line-height:1.25;color:var(--teal);margin:0 0 8px}
    .cr-steps p{font-size:16.5px;line-height:1.6;color:#4a5856;margin:0;max-width:22em}

    /* Apply */
    .cr-apply{display:grid;grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr);gap:64px;align-items:start}
    .cr-aside{position:sticky;top:112px}
    .cr-need{list-style:none;margin:26px 0 30px;padding:0;display:grid;gap:14px}
    .cr-need li{display:flex;gap:12px;align-items:flex-start;font-size:17px;line-height:1.45;color:#33403f}
    .cr-need svg{flex:none;width:24px;height:24px;margin-top:1px;stroke:var(--orange);fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
    .cr-where{border-top:1px solid var(--rule);padding-top:22px;display:grid;gap:6px}
    .cr-where p{font-size:16px;line-height:1.55;color:#4a5856;margin:0}
    .cr-where b{font-family:SemiBold;color:var(--teal)}
    .cr-where a{color:var(--teal);font-family:SemiBold;text-decoration:underline;text-underline-offset:3px}.cr-where a:hover{color:var(--orange)}
    .cr-card{background:#fff;border-radius:28px;padding:38px 38px 40px;box-shadow:0 30px 70px rgba(0,61,60,.12);border:1px solid #efece5}
    .cr-card h3.t{font-family:Bold;font-size:24px;color:var(--teal);margin:0 0 6px}
    .cr-card p.s{font-size:16px;color:var(--muted);margin:0 0 26px}
    .cr-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 16px}
    .cr-f{min-width:0}.cr-f--wide{grid-column:1/-1}
    .cr-form .lb{display:block;font-family:SemiBold;font-size:15.5px;color:var(--teal);margin:0 0 7px}
    .cr-form .lb small{font-family:SansPro_Regular,sans-serif;font-size:14px;color:#7a8684}
    .cr-form input[type=text],.cr-form input[type=email],.cr-form input[type=tel],.cr-form input[type=url],.cr-form select,.cr-form textarea{width:100%;min-height:54px;border:1.5px solid #d9e0de;border-radius:14px;padding:13px 16px;font-size:16.5px;font-family:SansPro_Regular,sans-serif;color:var(--ink);background:#fcfdfc;margin:0;box-sizing:border-box;-webkit-appearance:none;appearance:none;transition:border-color .15s,box-shadow .15s,background .15s}
    .cr-form select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23003d3c' stroke-width='2'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 18px center;background-size:12px;padding-right:44px;font-family:SemiBold}
    .cr-form input:hover,.cr-form select:hover,.cr-form textarea:hover{border-color:#b9c7c5}
    .cr-form input:focus,.cr-form select:focus,.cr-form textarea:focus{outline:none;border-color:var(--orange);background:#fff;box-shadow:0 0 0 4px rgba(255,107,53,.14)}
    .cr-form [aria-invalid=true]{border-color:#b91c1c;background:#fffafa}
    .cr-form textarea{min-height:150px;resize:vertical;display:block}
    .cr-form .hint{font-size:14px;color:#7a8684;margin:7px 0 0;display:flex;justify-content:space-between;gap:12px}
    .cr-form .err{color:#b91c1c;font-size:14.5px;margin:7px 0 0}
    .cr-file{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;border:1.5px dashed #b9c7c5;border-radius:14px;padding:14px 16px;background:#f7faf9}
    .cr-file input{position:absolute;width:1px;height:1px;opacity:0;overflow:hidden}
    .cr-file label.pick{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 18px;border-radius:999px;border:1.5px solid var(--teal);color:var(--teal);font-family:SemiBold;font-size:15.5px;cursor:pointer;background:#fff;margin:0;transition:background .15s,color .15s}
    .cr-file label.pick svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
    .cr-file label.pick:hover{background:var(--teal);color:#fff}
    .cr-file input:focus-visible + label.pick{outline:3px solid rgba(255,107,53,.6);outline-offset:2px}
    .cr-file .name{font-size:15px;color:#4a5856;word-break:break-all;flex:1 1 160px}
    .cr-file button.clear{background:none;border:0;padding:8px;color:#b91c1c;font-family:SemiBold;font-size:14.5px;cursor:pointer;min-height:44px}
    .cr-agree{display:flex;gap:12px;align-items:flex-start;font-size:15.5px;line-height:1.5;color:#4a5856;margin:0;cursor:pointer}
    .cr-agree input{width:22px;height:22px;margin:1px 0 0;flex:none;accent-color:var(--teal)}
    .cr-agree a{color:var(--teal);text-decoration:underline}
    .cr-send{display:flex;flex-wrap:wrap;align-items:center;gap:12px 18px;margin-top:4px}
    .cr-send .cr-btn{min-width:220px}.cr-send .cr-btn[disabled]{opacity:.65;cursor:progress}
    .cr-send span{font-size:14.5px;color:#7a8684}
    .cr-alert{grid-column:1/-1;background:#fdf1f0;border-left:4px solid #b91c1c;border-radius:0 12px 12px 0;padding:13px 16px;font-size:15.5px;line-height:1.5;color:#7f1d1d}
    .cr-done{text-align:center;padding:26px 8px 12px}
    .cr-done,.cr-alert{scroll-margin-top:120px}
    .cr-done svg{width:64px;height:64px;stroke:var(--teal);fill:var(--mint);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;display:block;margin:0 auto 18px}
    .cr-done h3{font-family:Bold;font-size:28px;color:var(--teal);margin:0 0 10px;text-align:center}
    .cr-done p{font-size:17.5px;line-height:1.6;color:#33403f;margin:0 auto 22px;max-width:26em;text-align:center}
    .cr-done a{color:var(--teal);font-family:SemiBold;text-decoration:underline;text-underline-offset:3px}
    .cr-bar{display:none}

    @media (max-width:1100px){.cr-teams{grid-template-columns:repeat(3,minmax(0,1fr))}.cr-chip--a{left:-10px}.cr-job .tags{flex-wrap:wrap;max-width:230px}}
    @media (max-width:900px){
        .cr-hero{padding-top:128px}
        .cr-hero__grid{grid-template-columns:1fr;gap:8px}
        .cr-hero__copy{padding-bottom:36px}
        .cr-hero__art{max-width:460px;width:100%;margin:0 auto;padding-bottom:44px}
        .cr-chip--a{left:0;bottom:120px}.cr-chip--b{right:0;bottom:64px}
        .cr-facts ul{grid-template-columns:repeat(2,minmax(0,1fr))}
        .cr-facts li{padding:24px 20px;border-top:1px solid var(--rule)}
        .cr-facts li:nth-child(odd){border-left:0;padding-left:0}
        .cr-facts li:nth-child(-n+2){border-top:0}
        .cr-sec{padding:64px 0}
        .cr-job{grid-template-columns:minmax(0,1fr) 46px;padding:22px 20px;gap:10px 14px}
        .cr-job .no{display:none}
        .cr-job .tags{grid-column:1;grid-row:2;justify-content:flex-start;max-width:none;flex-wrap:wrap}
        .cr-job .go{grid-column:2;grid-row:1 / span 2;width:46px;height:46px}
        .cr-steps{grid-template-columns:1fr;gap:26px}
        .cr-steps li:not(:last-child)::after{left:27px;right:auto;top:64px;bottom:-22px;border-top:0;border-left:2px dashed #cfdad8}
        .cr-steps li{padding:4px 0 0 80px;min-height:56px}
        .cr-steps p{max-width:none}
        .cr-apply{grid-template-columns:1fr;gap:36px}
        .cr-aside{position:static}
    }
    @media (max-width:640px){
        .cr-wrap{padding:0 18px}
        .cr-sec{padding:52px 0}
        .cr-head{margin-bottom:28px}
        .cr-actions .cr-btn{flex:1 1 100%}
        .cr-chip{padding:9px 14px 9px 9px;border-radius:14px}.cr-chip i{width:34px;height:34px;border-radius:10px}.cr-chip b{font-size:15.5px}.cr-chip small{font-size:12.5px}
        .cr-teams{grid-template-columns:1fr;gap:10px}
        .cr-team{display:grid;grid-template-columns:48px 1fr;column-gap:16px;padding:18px;border-radius:18px}
        .cr-team i{grid-row:1/3;margin:0}.cr-team h3{font-size:17.5px;margin-bottom:4px}
        .cr-jobs{border-radius:20px}
        .cr-card{padding:24px 18px 26px;border-radius:22px}
        .cr-form{grid-template-columns:1fr}
        .cr-send .cr-btn{width:100%}
        .cr-sec--apply{padding-bottom:104px}
        .cr-bar{display:block;position:fixed;left:0;right:0;bottom:0;z-index:40;padding:10px 16px calc(10px + env(safe-area-inset-bottom,0px));background:rgba(255,255,255,.97);border-top:1px solid var(--rule);transform:translateY(110%);transition:transform .22s}
        .cr-bar.on{transform:none}.cr-bar .cr-btn{width:100%}
    }
    @media (prefers-reduced-motion:reduce){.cr *,.cr-bar{animation:none!important;transition:none!important}}
    </style>
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs><clipPath id="cr-house" clipPathUnits="objectBoundingBox"><path d="M.43 .05 Q.5 0 .57 .05 L.93 .31 Q1 .36 1 .45 L1 .9 Q1 1 .9 1 L.1 1 Q0 1 0 .9 L0 .45 Q0 .36 .07 .31 Z"/></clipPath></defs></svg>
    @php $arrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>'; @endphp

    <div class="cr">
        <header class="cr-hero">
            <div class="cr-wrap cr-hero__grid">
                <div class="cr-hero__copy">
                    @if($focus)
                    <p class="cr-eyebrow"><a href="/careers" style="color:inherit;text-decoration:none">Careers at MOKA</a> · Open role</p>
                    <h1>{{ $focus['title'] }}</h1>
                    <p class="cr-hero__tags">@foreach(array_filter([$focus['type'] ?? null, $focus['location'] ?? null]) as $tag)<span>{{ $tag }}</span>@endforeach</p>
                    <p class="cr-hero__lead">{{ $focus['summary'] ?? '' }}</p>
                    @if(!empty($focus['points']))<ul class="cr-hero__pts">@foreach($focus['points'] as $point)<li>{{ $point }}</li>@endforeach</ul>@endif
                    <div class="cr-actions">
                        <a class="cr-btn cr-btn--go" href="#apply">Apply for this role {!! $arrow !!}</a>
                        <a class="cr-btn cr-btn--line" href="/careers#roles">All open roles</a>
                    </div>
                    @else
                    <p class="cr-eyebrow">Careers at MOKA</p>
                    <h1>Hosting is what we do. <em>Come do it with us.</em></h1>
                    <p class="cr-hero__lead">We run homes like hotels for their owners. If you care about guests, clean rooms and getting the details right, there is a place for you here.</p>
                    <div class="cr-actions">
                        <a class="cr-btn cr-btn--go" href="#roles">See open roles {!! $arrow !!}</a>
                        <a class="cr-btn cr-btn--line" href="#apply">Apply now</a>
                    </div>
                    @endif
                </div>
                <div class="cr-hero__art">
                    <img src="{{ asset('new-theme23/images/projects/skyawani-4/1.webp') }}" width="1280" height="1600" alt="Dining and living area of an apartment managed by MOKA" fetchpriority="high" decoding="async">
                    <div class="cr-chip cr-chip--a"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12l9-8 9 8M5 10v10h14V10"/></svg></i><span><b>Since 2018</b><small>hosting in Malaysia</small></span></div>
                    <div class="cr-chip cr-chip--b"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-9 0h12a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg></i><span><b>{{ $roleCount ?: 'Open' }} {{ $roleCount === 1 ? 'open role' : ($roleCount ? 'open roles' : 'applications') }}</b><small>{{ $roleCount ? 'apply online in minutes' : 'tell us what you do best' }}</small></span></div>
                </div>
            </div>
        </header>

        @unless($focus)
        <div class="cr-facts">
            <div class="cr-wrap">
                <ul>
                    <li><b>2018</b><span>The year we started hosting</span></li>
                    <li><b>5 teams</b><span>From front desk to finance</span></li>
                    <li><b>{{ $roleCount ?: 'Always' }}{{ $roleCount ? ' open' : ' open' }}</b><span>{{ $roleCount ? ($roleCount === 1 ? 'Role to apply for today' : 'Roles to apply for today') : 'To a good application' }}</span></li>
                    <li><b>EkoCheras</b><span>Our base in Kuala Lumpur</span></li>
                </ul>
            </div>
        </div>

        <section class="cr-sec" aria-labelledby="cr-h-teams">
            <div class="cr-wrap">
                <div class="cr-head">
                    <div>
                        <p class="cr-eyebrow">Where you could work</p>
                        <h2 id="cr-h-teams">Five teams, one job: a great stay.</h2>
                    </div>
                    <p class="cr-lead">MOKA has managed short-stay, monthly and corporate rentals since 2018, from our office at EkoCheras and on site in the buildings we run.</p>
                </div>
                <ul class="cr-teams">
                    @foreach($teams as [$name, $text, $icon])
                        <li class="cr-team"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg></i><h3>{{ $name }}</h3><p>{{ $text }}</p></li>
                    @endforeach
                </ul>
            </div>
        </section>
        @endunless

        <section class="cr-sec cr-sec--cream" id="roles" aria-labelledby="cr-h-roles">
            <div class="cr-wrap">
                <div class="cr-head">
                    <div>
                        <p class="cr-eyebrow">{{ $focus ? 'More roles' : 'Open roles' }}</p>
                        <h2 id="cr-h-roles">{{ $focus ? 'Other open roles.' : 'Find your place.' }}</h2>
                    </div>
                    @if($roleCount && ! $focus)<span class="cr-count">{{ $roleCount }} {{ $roleCount === 1 ? 'role' : 'roles' }} open now</span>@endif
                </div>
                <div class="cr-jobs">
                    @php $rowNo = 0; @endphp
                    @foreach($roles as $role)
                        @continue($focus && $focus['slug'] === $role['slug'])
                        @php $rowNo++; @endphp
                        <a class="cr-job" href="/careers/{{ $role['slug'] }}#apply" id="role-{{ $role['slug'] }}">
                            <span class="no" aria-hidden="true">{{ str_pad((string) $rowNo, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="txt">
                                <h3>{{ $role['title'] }}</h3>
                                @if(!empty($role['summary']))<p class="sum">{{ $role['summary'] }}</p>@endif
                                @if(!empty($role['points']))<ul class="pts">@foreach($role['points'] as $point)<li>{{ $point }}</li>@endforeach</ul>@endif
                            </span>
                            <span class="tags">@if(!empty($role['type']))<span class="t">{{ $role['type'] }}</span>@endif @if(!empty($role['location']))<span>{{ $role['location'] }}</span>@endif</span>
                            <span class="go" aria-hidden="true">{!! $arrow !!}</span>
                        </a>
                    @endforeach
                    <a class="cr-job cr-job--open" href="#apply" data-role="general">
                        <span class="no" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
                        <span class="txt">
                            <h3>{{ $roleCount ? 'Don’t see your role?' : 'General application' }}</h3>
                            <p class="sum">Send a general application and tell us the kind of work you are looking for.</p>
                        </span>
                        <span class="tags"><span>Any team</span></span>
                        <span class="go" aria-hidden="true">{!! $arrow !!}</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="cr-sec" aria-labelledby="cr-h-steps">
            <div class="cr-wrap">
                <div class="cr-head">
                    <div>
                        <p class="cr-eyebrow">How it works</p>
                        <h2 id="cr-h-steps">Three steps, no runaround.</h2>
                    </div>
                </div>
                <ol class="cr-steps">
                    <li><h3>Apply online</h3><p>Fill in the short form below. It goes straight to our team.</p></li>
                    <li><h3>We get in touch</h3><p>If there is a match, we contact you by phone or email.</p></li>
                    <li><h3>We meet</h3><p>In person at EkoCheras, or online if that is easier.</p></li>
                </ol>
            </div>
        </section>

        <section class="cr-sec cr-sec--cream cr-sec--apply" id="apply" aria-labelledby="cr-h-apply">
            <div class="cr-wrap cr-apply">
                <div class="cr-aside">
                    <p class="cr-eyebrow">Apply</p>
                    <h2 id="cr-h-apply">Ready when you are.</h2>
                    <p class="cr-lead">It takes about three minutes. Here is all you need.</p>
                    <ul class="cr-need">
                        @foreach(['Your name, email and phone number', 'A few lines about you and the work you want', 'Your CV, if you have one'] as $need)
                            <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/></svg>{{ $need }}</li>
                        @endforeach
                    </ul>
                    <div class="cr-where">
                        <p><b>Office</b> · EkoCheras, Jalan Cheras, 56000 Kuala Lumpur</p>
                        <p><b>On site</b> · the other buildings we manage in Kuala Lumpur and Selangor</p>
                        <p><b>A question first?</b> · <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">Chat on WhatsApp</a> or <a href="/contact">send a message</a></p>
                    </div>
                </div>

                <div class="cr-card">
                    @if($sent)
                        <div class="cr-done" id="cr-done" role="status" tabindex="-1">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M7.8 12.4l2.8 2.8 5.6-5.8"/></svg>
                            <h3>Application received</h3>
                            <p>{{ $sent }}</p>
                            <a href="/careers#apply">Send another application</a>
                        </div>
                    @else
                    <h3 class="t">Your application</h3>
                    <p class="s">Fields marked optional can be left empty.</p>
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
                            <span class="lb" id="cr-cv-l">CV <small>(optional)</small></span>
                            <div class="cr-file">
                                <input id="cr-cv" type="file" name="cv" aria-labelledby="cr-cv-l" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                <label class="pick" for="cr-cv"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0l-4 4m4-4l4 4M5 20h14"/></svg>Choose a file</label>
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
            </div>
        </section>
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
    // After sending, the visitor must land on the result: the confirmation, or the list of what to fix.
    // Run now and again once the page has finished loading, because the browser's own jump runs in between.
    var show = function () {
        if (done) { done.scrollIntoView({ block: 'center', behavior: 'instant' }); done.focus({ preventScroll: true }); }
        if (alertBox) { alertBox.scrollIntoView({ block: 'start', behavior: 'instant' }); var bad = form.querySelector('[aria-invalid=true]'); (bad || alertBox).focus({ preventScroll: true }); }
    };
    if (done || alertBox) { show(); window.addEventListener('load', function () { setTimeout(show, 60); }); }
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
    var bar = $('cr-bar'), hero = document.querySelector('.cr-actions');
    if (bar && hero && 'IntersectionObserver' in window) {
        var seen = { hero: true, form: false };
        var set = function () { bar.classList.toggle('on', !seen.hero && !seen.form); };
        new IntersectionObserver(function (en) { seen.hero = en[0].isIntersecting; set(); }).observe(hero);
        new IntersectionObserver(function (en) { seen.form = en[0].isIntersecting; set(); }, { rootMargin: '0px 0px -20% 0px' }).observe(form);
    }
})();
</script>
@endpush

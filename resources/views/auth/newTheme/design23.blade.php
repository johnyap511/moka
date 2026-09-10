@extends('auth.newTheme.layout')
@section('seo_title', 'Renovation & Interior Design by Innspace | SkyWorld Solution+ Panel Renovator | MOKA')
@section('seo_description', 'Innspace, MOKA’s sister company: 8 years of renovation and custom interior design for Malaysian homeowners, panel renovator on SkyWorld Solution+, renovation before vacant possession, MyDeco financing with Maybank. Chat for a quotation.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/designs23.css') }}?v={{ filemtime(public_path('new-theme23/css/designs23.css')) }}">
    @if(request('flat'))<style>.inn-hero{min-height:0;padding:130px 0 70px}</style>@endif {{-- review aid: ?flat=1 shortens the hero for full-page captures --}}
@endpush

@php
    $wa = 'https://wa.me/message/GJMYMABOT7CSG1?text=' . rawurlencode('Hi Innspace, I would like a renovation quotation.');
    $projects = [
        ['slug' => 'the-valley', 'name' => 'The Valley', 'blurb' => 'Fully furnished units and the SkyWorld showroom, warm timber and soft neutrals.', 'rooms' => ['Living', 'Living', 'Living', 'Kitchen', 'Dining', 'Living', 'Kitchen', 'Bedroom']],
        ['slug' => 'skyawani-4', 'name' => 'Sky Awani 4', 'blurb' => 'Showroom and owner units: light kitchens, built-in storage, calm bedrooms.', 'rooms' => ['Living and dining', 'Living', 'Dining', 'Bedroom', 'Kitchen', 'Bedroom', 'Dining', 'Living']],
        ['slug' => 'skyawani-5', 'name' => 'Sky Awani 5', 'blurb' => 'Kitchen packages in sage and oak, with full-height cabinetry.', 'rooms' => ['Kitchen', 'Kitchen', 'Kitchen', 'Kitchen', 'Kitchen', 'Kitchen']],
        ['slug' => 'curvo', 'name' => 'Curvo', 'blurb' => 'Walnut joinery, cove lighting and island kitchens with city views.', 'rooms' => ['Living', 'Living', 'Kitchen', 'Kitchen island', 'Living', 'Wardrobe']],
        ['slug' => 'skyvogue', 'name' => 'SkyVogue', 'blurb' => 'A premium custom ID package: layered lighting, curved sofa, walnut and forest green.', 'rooms' => ['Living', 'Living', 'Kitchen', 'Dining', 'Bedroom', 'Dining']],
    ];
@endphp

@section('content')
    <div>
    @include('auth.newTheme.partials.header')
<script>
    // change header color on scroll
    let header = document.getElementById("main_header");
    header.classList.remove("bg-orange");
    window.addEventListener("scroll", ()=>{
        if(window.scrollY > 5){ header.classList.add("bg-orange"); header.style.boxShadow = "0px 0px 20px -3px rgba(0,0,0,0.1)"; }
        else{ header.classList.remove("bg-orange"); header.style.boxShadow = "none"; }
    })
    let menuExpand = (e) => { let expandIcon = e.querySelector(".fa-chevron-up"); expandIcon.classList.toggle("active"); }
</script>

    {{-- Hero --}}
    <section class="inn-hero" style="background-image:url('{{ asset('new-theme23/images/projects/hero.webp') }}')">
        <div class="container">
            <img src="{{ asset('new-theme23/images/innspace-logo-white.png') }}?v={{ filemtime(public_path('new-theme23/images/innspace-logo-white.png')) }}" alt="Innspace" class="inn-hero__logo">
            <div class="inn-eyebrow">Innspace, a MOKA company</div>
            <h1>Renovation and interior design, finished before you move in</h1>
            <p>Eight years of renovation for Malaysian homeowners, panel renovator on SkyWorld Solution+, and one of the first in the market to renovate before vacant possession. Quality you can see, with a warranty behind it.</p>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="primary-btn">Chat us for a quotation</a>
            <a href="#projects" class="white-btn">See our projects</a>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="inn-stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-6 col-md-3"><h2>8 years</h2><p>Renovation experience</p></div>
                <div class="col-6 col-md-3"><h2>Warranty</h2><p>On every project we hand over</p></div>
                <div class="col-6 col-md-3"><h2>Solution+</h2><p>SkyWorld panel renovator</p></div>
                <div class="col-6 col-md-3"><h2>Before VP</h2><p>Move in on handover day</p></div>
            </div>
        </div>
    </section>

    {{-- What we do --}}
    <section class="inn-section" id="services">
        <div class="container">
            <h2 class="heading-orange-1 text-center">What Innspace does</h2>
            <p class="inn-lead text-center">Three ways to get a home that is ready to live in, or ready to earn, from one accountable team.</p>
            <div class="row g-4">
                <div class="col-md-4"><div class="inn-card"><div class="num">01</div><h3>Renovation before vacant possession</h3><p>Design and works are completed inside the developer's pre-VP schedule, so you collect your keys to a finished, cleaned home. No months of instalments on an empty unit.</p><ul><li>Defects logged before we touch a surface</li><li>Sequenced with the developer, not the lift queue</li><li>Move in, or start earning, on VP day</li></ul></div></div>
                <div class="col-md-4"><div class="inn-card"><div class="num">02</div><h3>Premium custom ID packages</h3><p>A design developed for your unit and how you live in it: space planning, joinery, lighting, materials and styling, presented in drawings and 3D so you approve the real thing.</p><ul><li>Custom design, not a package on a floor plan</li><li>Fixed scope and quote before works begin</li><li>Durable finishes for family or rental use</li></ul></div></div>
                <div class="col-md-4"><div class="inn-card"><div class="num">03</div><h3>Full renovation with warranty</h3><p>Wet works, electrical, plaster ceilings, built-ins, flooring and painting, supervised on site through to handover, with a warranty on the work we deliver.</p><ul><li>Eight years of completed projects</li><li>Vetted panel renovator on Solution+</li><li>One team, accountable for the result</li></ul></div></div>
            </div>
        </div>
    </section>

    {{-- Panel renovator --}}
    <section class="inn-section inn-panel" id="panel">
        <div class="container">
            <div class="inn-panel__grid">
                <div>
                    <div class="inn-eyebrow inn-eyebrow--orange">SkyWorld Solution+ panel renovator</div>
                    <h2 class="heading-orange-1">Selected on quality. Safer for you.</h2>
                    <p>Innspace is one of the renovators SkyWorld appointed to its Solution+ marketplace after vetting our work. For an owner that means a contractor who was chosen on quality, works inside the developer's own rules and standards, and answers to SkyWorld as well as to you.</p>
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="primary-btn">Chat us for a quotation</a>
                </div>
                <ul class="inn-checklist">
                    <li><span class="tick"></span><div><b>Vetted, not found online</b><small>Appointed by the developer after reviewing our completed projects.</small></div></li>
                    <li><span class="tick"></span><div><b>Works to the developer's standards</b><small>Site rules, renovation windows and building procedures are already part of how we work.</small></div></li>
                    <li><span class="tick"></span><div><b>Accountable twice over</b><small>To SkyWorld through Solution+, and to you through our warranty.</small></div></li>
                    <li><span class="tick"></span><div><b>Defects protected</b><small>Developer defects are logged before we touch a surface, so your defect liability cover is preserved.</small></div></li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Projects --}}
    <section class="inn-section inn-section--cream" id="projects">
        <div class="container">
            <h2 class="heading-orange-1 text-center">Completed projects</h2>
            <p class="inn-lead text-center">Real units and showrooms across SkyWorld developments. Tap a photo to view it full size.</p>
            <div class="inn-tabs" role="tablist">
                @foreach($projects as $i => $p)
                    <button type="button" class="inn-tab {{ $i === 0 ? 'active' : '' }}" data-project="{{ $p['slug'] }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">{{ $p['name'] }}</button>
                @endforeach
            </div>
            @foreach($projects as $i => $p)
                <div class="inn-project" data-project="{{ $p['slug'] }}" @if($i !== 0) hidden @endif>
                    <p class="text-center" style="color:var(--green);font-size:17px;margin:0 0 20px">{{ $p['blurb'] }}</p>
                    <div class="inn-grid" data-count="{{ count($p['rooms']) }}">
                        @foreach($p['rooms'] as $idx => $room)
                            @php $k = $idx + 1; @endphp
                            <a href="{{ asset('new-theme23/images/projects/' . $p['slug'] . '/' . $k . '.jpg') }}" data-full="{{ asset('new-theme23/images/projects/' . $p['slug'] . '/' . $k . '.webp') }}" data-caption="{{ $p['name'] }} · {{ $room }}" data-room="{{ $room }}">
                                <picture>
                                    <source srcset="{{ asset('new-theme23/images/projects/' . $p['slug'] . '/' . ($k === 1 ? $k : $k . '-thumb') . '.webp') }}" type="image/webp">
                                    <img src="{{ asset('new-theme23/images/projects/' . $p['slug'] . '/' . ($k === 1 ? $k : $k . '-thumb') . '.jpg') }}" alt="{{ $p['name'] }} {{ strtolower($room) }}, renovation by Innspace" loading="lazy">
                                </picture>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p class="inn-more">Also completed: <span>Sky Meridien</span> · <span>Sky Awani 3</span> · <span>Sky Awani 6</span> · <span>Vesta</span></p>
        </div>
    </section>

    {{-- How it works --}}
    <section class="inn-section" id="how">
        <div class="container">
            <h2 class="heading-orange-1 text-center">How it works</h2>
            <p class="inn-lead text-center">From first chat to handover, the same four steps whether it is a single room or a whole unit before VP.</p>
            <div class="inn-steps">
                <div class="inn-step"><h3>Chat and brief</h3><p>Send us your unit, floor plan and how you will use the home. We reply with what is possible and a first budget range.</p></div>
                <div class="inn-step"><h3>Design and quote</h3><p>Space planning, materials and 3D views for your approval, then a fixed scope and quote. No variation surprises.</p></div>
                <div class="inn-step"><h3>Renovation</h3><p>Works run to a schedule, before VP where the developer allows, with site supervision and photo updates.</p></div>
                <div class="inn-step"><h3>Handover and warranty</h3><p>A cleaned, finished home, a defect walk-through together, and a warranty on the work we did.</p></div>
            </div>
        </div>
    </section>

    {{-- Financing --}}
    <section class="inn-section inn-section--cream" id="financing" style="padding-top:0">
        <div class="container">
            <div class="inn-finance">
                <div>
                    <h2>Pay for it with MyDeco, through Solution+</h2>
                    <p>SkyWorld buyers can fund the renovation with Maybank's MyDeco facility offered through Solution+, on top of the home loan rather than from savings. Arranging it early is what makes a pre-VP schedule possible.</p>
                    <p style="font-size:15px;opacity:.8">Terms and eligibility are set by SkyWorld and Maybank and change over time. Confirm the current numbers with them before you plan around them.</p>
                </div>
                <ul class="inn-facts">
                    <li><b>Facility</b><span>Maybank MyDeco, through SkyWorld Solution+</span></li>
                    <li><b>Who</b><span>SkyWorld homebuyers with a Maybank home loan</span></li>
                    <li><b>Covers</b><span>Renovation and interior design by a panel renovator</span></li>
                    <li><b>When to apply</b><span>Before the design is finalised, so the pre-VP slot holds</span></li>
                    <li><b>We help with</b><span>The quotation and documents the application needs</span></li>
                </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <div class="bottom-banner py-5" id="quote">
        <div class="container text-center">
            <h2 class="heading-white-2">Chat with us and get a quotation</h2>
            <p class="text-white-1 mt-3 mb-4" style="font-size:19px">Send your unit number and floor plan. We reply with what can be done, and by when.</p>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="white-btn">Chat on WhatsApp</a>
            <a href="/get/estimate" target="_blank" rel="noopener" class="primary-btn" style="border:3px solid #fff">Get a quick estimate</a>
        </div>
    </div>

    <div class="inn-lightbox" id="innLightbox" role="dialog" aria-label="Photo">
        <button type="button" class="x" aria-label="Close">&times;</button>
        <button type="button" class="prev" aria-label="Previous">&#8249;</button>
        <img src="" alt="">
        <button type="button" class="next" aria-label="Next">&#8250;</button>
        <div class="cap"></div>
    </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var tabs = document.querySelectorAll('.inn-tab'), panels = document.querySelectorAll('.inn-project');
    tabs.forEach(function (t) { t.addEventListener('click', function () {
        tabs.forEach(function (x) { x.classList.remove('active'); x.setAttribute('aria-selected', 'false'); });
        t.classList.add('active'); t.setAttribute('aria-selected', 'true');
        panels.forEach(function (p) { p.hidden = p.dataset.project !== t.dataset.project; });
    }); });
    var lb = document.getElementById('innLightbox'), img = lb.querySelector('img'), cap = lb.querySelector('.cap'), list = [], cur = 0;
    function show(i) { cur = (i + list.length) % list.length; var a = list[cur]; img.src = a.dataset.full || a.href; cap.textContent = a.dataset.caption + ' · ' + (cur + 1) + ' / ' + list.length; }
    document.querySelectorAll('.inn-grid a').forEach(function (a) { a.addEventListener('click', function (e) {
        e.preventDefault(); list = Array.prototype.slice.call(a.closest('.inn-grid').querySelectorAll('a')); show(list.indexOf(a)); lb.classList.add('open'); document.body.style.overflow = 'hidden';
    }); });
    function close() { lb.classList.remove('open'); document.body.style.overflow = ''; img.src = ''; }
    lb.querySelector('.x').addEventListener('click', close);
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    lb.querySelector('.prev').addEventListener('click', function () { show(cur - 1); });
    lb.querySelector('.next').addEventListener('click', function () { show(cur + 1); });
    document.addEventListener('keydown', function (e) { if (!lb.classList.contains('open')) return; if (e.key === 'Escape') close(); if (e.key === 'ArrowLeft') show(cur - 1); if (e.key === 'ArrowRight') show(cur + 1); });
})();
</script>
@endpush

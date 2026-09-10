@extends('auth.newTheme.layout')
@section('seo_title', 'Renovation & Interior Design in KL | Innspace by MOKA, SkyWorld Solution+ Panel Renovator')
@section('seo_description', 'KL renovation and interior design by Innspace, MOKA’s sister company and SkyWorld Solution+ panel renovator: renovate before VP, premium custom ID packages, MyDeco financing, plus handyman services for owners in Kuala Lumpur: AC servicing, repairs, painting touch-up and cabinet restoration.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/designs23.css') }}?v={{ filemtime(public_path('new-theme23/css/designs23.css')) }}">
    @if(request('flat'))<style>.inn-hero{min-height:0;padding:130px 0 70px}</style>@endif {{-- review aid: ?flat=1 shortens the hero for full-page captures --}}
@endpush

@php
    $wa = 'https://wa.me/message/GJMYMABOT7CSG1?text=' . rawurlencode('Hi Innspace, I would like a renovation quotation.');
    $renovated = ['The Valley', 'Sky Meridien', 'Sky Awani 3', 'Sky Awani 4', 'Sky Awani 5', 'Sky Awani 6', 'Vesta', 'Curvo', 'SkyVogue'];
    $projects = [
        ['slug' => 'the-valley', 'name' => 'The Valley', 'blurb' => 'Fully furnished owner units and the SkyWorld showroom. Warm timber, soft neutrals and storage worked into every wall.', 'shots' => [1 => 'Living', 2 => 'Living', 5 => 'Dining', 7 => 'Kitchen']],
        ['slug' => 'skyawani-4', 'name' => 'Sky Awani 4', 'blurb' => 'Showroom and owner units. Light kitchens, built-in wardrobes and calm bedrooms designed for families and long stays.', 'shots' => [1 => 'Living and dining', 2 => 'Living', 3 => 'Dining', 4 => 'Bedroom', 5 => 'Kitchen', 6 => 'Bedroom', 7 => 'Dining', 8 => 'Living']],
        ['slug' => 'skyvogue', 'name' => 'SkyVogue', 'blurb' => 'A premium custom ID package. Layered lighting, a curved sofa, walnut joinery and forest-green tiles.', 'shots' => [1 => 'Living', 2 => 'Living', 3 => 'Kitchen', 4 => 'Dining', 5 => 'Bedroom', 6 => 'Dining']],
    ];
    $photos = [];
    foreach ($projects as $p) { foreach ($p['shots'] as $k => $room) { $photos[] = ['slug' => $p['slug'], 'k' => $k, 'name' => $p['name'], 'room' => $room, 'blurb' => $p['blurb']]; } }
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
            <div class="inn-eyebrow">Innspace, a MOKA company · Kuala Lumpur</div>
            <h1>Renovation and interior design, finished before you move in</h1>
            <p>Eight years of renovation for Malaysian homeowners, panel renovator on SkyWorld Solution+, and one of the first in the market to renovate before vacant possession. Quality you can see, with a warranty behind it.</p>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="primary-btn">Chat us for a quotation</a>
            <a href="#projects" class="white-btn">See our projects</a>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="inn-stats">
        <div class="inn-stats__grid">
            <div><h2>8 years</h2><p>Renovation experience</p></div>
            <div><h2>Warranty</h2><p>On every project we hand over</p></div>
            <div><h2>Solution+</h2><p>SkyWorld panel renovator</p></div>
            <div><h2>Before VP</h2><p>Move in on handover day</p></div>
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
                    <div class="inn-partners">
                        <span>Panel renovator appointed by</span>
                        <div class="inn-partners__logos">
                            <img src="{{ asset('new-theme23/images/logo-solution-plus.png') }}?v={{ filemtime(public_path('new-theme23/images/logo-solution-plus.png')) }}" alt="Solution+ by SkyWorld" class="inn-partners__sp">
                            <img src="{{ asset('new-theme23/images/logo-skyworld.png') }}?v={{ filemtime(public_path('new-theme23/images/logo-skyworld.png')) }}" alt="SkyWorld" class="inn-partners__sw">
                        </div>
                    </div>
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
            <h2 class="heading-orange-1 text-center">Completed renovation projects in KL</h2>
            <p class="inn-lead text-center">Nine SkyWorld developments in Kuala Lumpur, from single owner units to full showrooms.</p>
            <ul class="inn-index" aria-label="Developments renovated by Innspace">
                @foreach($renovated as $r)<li>{{ $r }}</li>@endforeach
            </ul>

            <div class="inn-show" id="innShow">
                <div class="inn-show__stage">
                    <picture>
                        <source id="innStageWebp" srcset="{{ asset('new-theme23/images/projects/' . $photos[0]['slug'] . '/1.webp') }}" type="image/webp">
                        <img id="innStageImg" src="{{ asset('new-theme23/images/projects/' . $photos[0]['slug'] . '/1.jpg') }}" alt="{{ $photos[0]['name'] }} {{ strtolower($photos[0]['room']) }}, renovation by Innspace" width="1600" height="1000">
                    </picture>
                    <button type="button" class="inn-show__arrow inn-show__arrow--prev" id="innPrev" aria-label="Previous photo"><svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></button>
                    <button type="button" class="inn-show__arrow inn-show__arrow--next" id="innNext" aria-label="Next photo"><svg viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></button>
                    <button type="button" class="inn-show__zoom" id="innZoom" aria-label="View full size"><svg viewBox="0 0 24 24"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></button>
                </div>
                <div class="inn-show__panel">
                    <div>
                        <div class="inn-show__eyebrow">Project <span id="innCount">1</span> of {{ count($photos) }}</div>
                        <h3 id="innName">{{ $photos[0]['name'] }}</h3>
                        <div class="inn-show__room" id="innRoom">{{ $photos[0]['room'] }}</div>
                        <p id="innBlurb">{{ $photos[0]['blurb'] }}</p>
                    </div>
                    <div class="inn-show__foot">
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="inn-show__link">Talk to us about a similar renovation <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                        <span class="inn-show__hint">Fully furnished units: The Valley, Sky Awani 4, SkyVogue</span>
                    </div>
                </div>
            </div>
            <div class="inn-thumbs" id="innThumbs" role="tablist" aria-label="Project photos">
                @foreach($photos as $i => $ph)
                    <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-i="{{ $i }}" data-slug="{{ $ph['slug'] }}" data-k="{{ $ph['k'] }}" data-name="{{ $ph['name'] }}" data-room="{{ $ph['room'] }}" data-blurb="{{ $ph['blurb'] }}" aria-label="{{ $ph['name'] }}, {{ $ph['room'] }}">
                        <picture>
                            <source srcset="{{ asset('new-theme23/images/projects/' . $ph['slug'] . '/' . $ph['k'] . '-thumb.webp') }}" type="image/webp">
                            <img src="{{ asset('new-theme23/images/projects/' . $ph['slug'] . '/' . $ph['k'] . '-thumb.jpg') }}" alt="" loading="lazy" width="720" height="540">
                        </picture>
                    </button>
                @endforeach
            </div>
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

    {{-- Handyman --}}
    <section class="inn-section" id="handyman">
        <div class="container">
            <h2 class="heading-orange-1 text-center">Handyman services in KL for owners</h2>
            <p class="inn-lead text-center">The same team that renovates also keeps units in shape. For units under MOKA management, and for KL homeowners on request.</p>
            <div class="row g-4">
                <div class="col-6 col-lg-3"><div class="inn-card inn-card--sm"><div class="num">AC</div><h3>Air-conditioner servicing</h3><p>Chemical wash, gas top-up, fault diagnosis and replacement, scheduled so guests and tenants are never left in a warm unit.</p></div></div>
                <div class="col-6 col-lg-3"><div class="inn-card inn-card--sm"><div class="num">FIX</div><h3>Repairs</h3><p>Plumbing leaks, electrical faults, door locks, water heaters, fittings and fixtures, attended by our own handymen.</p></div></div>
                <div class="col-6 col-lg-3"><div class="inn-card inn-card--sm"><div class="num">PAINT</div><h3>Painting touch-up</h3><p>Scuffs, marks and wear between tenancies, colour-matched and touched up, or a full repaint when a unit turns over.</p></div></div>
                <div class="col-6 col-lg-3"><div class="inn-card inn-card--sm"><div class="num">WOOD</div><h3>Cabinet restoration</h3><p>Kitchen and wardrobe cabinets re-laminated, re-hinged and repaired rather than replaced, keeping the original design.</p></div></div>
            </div>
            <p class="text-center mt-4" style="font-size:16px;color:var(--green)">Owners with MOKA can ask their account manager. Others, <a href="{{ $wa }}" target="_blank" rel="noopener" style="color:var(--orange);font-family:SemiBold">chat with us on WhatsApp</a>.</p>
        </div>
    </section>

    {{-- Financing --}}
    <section class="inn-section inn-section--cream" id="financing">
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
    var base = "{{ asset('new-theme23/images/projects') }}/";
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('#innThumbs button')), cur = 0, timer = null;
    var img = document.getElementById('innStageImg'), webp = document.getElementById('innStageWebp');
    var lb = document.getElementById('innLightbox'), lbImg = lb.querySelector('img'), cap = lb.querySelector('.cap');
    function show(i, user) {
        cur = (i + thumbs.length) % thumbs.length; var t = thumbs[cur], d = t.dataset;
        img.style.opacity = 0;
        setTimeout(function () {
            webp.srcset = base + d.slug + '/' + d.k + '.webp'; img.src = base + d.slug + '/' + d.k + '.jpg';
            img.alt = d.name + ' ' + d.room.toLowerCase() + ', renovation by Innspace';
            document.getElementById('innCount').textContent = cur + 1;
            document.getElementById('innName').textContent = d.name;
            document.getElementById('innRoom').textContent = d.room;
            document.getElementById('innBlurb').textContent = d.blurb;
            img.onload = function () { img.style.opacity = 1; };
        }, 180);
        thumbs.forEach(function (b) { b.classList.remove('active'); });
        t.classList.add('active');
        t.scrollIntoView({ block: 'nearest', inline: 'center', behavior: user ? 'smooth' : 'auto' });
        if (user) restart();
    }
    function restart() { clearInterval(timer); timer = setInterval(function () { show(cur + 1); }, 5500); }
    thumbs.forEach(function (b) { b.addEventListener('click', function () { show(+b.dataset.i, true); }); });
    document.getElementById('innPrev').addEventListener('click', function () { show(cur - 1, true); });
    document.getElementById('innNext').addEventListener('click', function () { show(cur + 1, true); });
    var stage = document.querySelector('.inn-show__stage');
    stage.addEventListener('mouseenter', function () { clearInterval(timer); });
    stage.addEventListener('mouseleave', restart);
    var sx = 0; stage.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) { var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 40) show(cur + (dx < 0 ? 1 : -1), true); });
    function openLb() { var d = thumbs[cur].dataset; lbImg.src = base + d.slug + '/' + d.k + '.webp'; cap.textContent = d.name + ' · ' + d.room + ' · ' + (cur + 1) + ' / ' + thumbs.length; lb.classList.add('open'); document.body.style.overflow = 'hidden'; clearInterval(timer); }
    function closeLb() { lb.classList.remove('open'); document.body.style.overflow = ''; lbImg.src = ''; restart(); }
    document.getElementById('innZoom').addEventListener('click', openLb);
    img.addEventListener('click', openLb);
    lb.querySelector('.x').addEventListener('click', closeLb);
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });
    lb.querySelector('.prev').addEventListener('click', function () { show(cur - 1); var d = thumbs[cur].dataset; lbImg.src = base + d.slug + '/' + d.k + '.webp'; cap.textContent = d.name + ' · ' + d.room + ' · ' + (cur + 1) + ' / ' + thumbs.length; });
    lb.querySelector('.next').addEventListener('click', function () { show(cur + 1); var d = thumbs[cur].dataset; lbImg.src = base + d.slug + '/' + d.k + '.webp'; cap.textContent = d.name + ' · ' + d.room + ' · ' + (cur + 1) + ' / ' + thumbs.length; });
    document.addEventListener('keydown', function (e) { if (lb.classList.contains('open')) { if (e.key === 'Escape') closeLb(); if (e.key === 'ArrowLeft') lb.querySelector('.prev').click(); if (e.key === 'ArrowRight') lb.querySelector('.next').click(); } });
    restart();
    document.addEventListener('contextmenu', function (e) { if (e.target.closest('.inn-show__stage, .inn-thumbs, .inn-lightbox, .inn-hero')) e.preventDefault(); });
    document.addEventListener('dragstart', function (e) { if (e.target.tagName === 'IMG') e.preventDefault(); });
})();
</script>
@endpush

@push('schema')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'HomeAndConstructionBusiness', '@id' => url('/designs') . '#innspace', 'name' => 'Innspace by MOKA', 'alternateName' => 'Inn Space Interior', 'parentOrganization' => ['@id' => url('/') . '#organization'], 'url' => url('/designs'), 'image' => asset('new-theme23/images/projects/hero.jpg'), 'areaServed' => ['Kuala Lumpur', 'Selangor'], 'telephone' => '+60367892288', 'email' => 'hello@homemoka.com',
        'makesOffer' => array_map(fn ($n) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $n, 'areaServed' => 'Kuala Lumpur']], ['Home renovation in KL', 'Interior design in KL', 'Renovation before vacant possession', 'Custom interior design packages', 'Air-conditioner servicing', 'Home repairs', 'Painting touch-up', 'Cabinet restoration'])],
    ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'Renovation and interior design', 'item' => url('/designs')]]],
]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

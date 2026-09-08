{{--
    Solutions index. Route: /solutions → Auth\WebController@solutions.
    Homepage template; styles in new-theme23/css/solutions-index23.css.
--}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Property Management Solutions in Malaysia: Airbnb, Short-Stay, Monthly Rental, Corporate Housing | MOKA')
@section('seo_description', 'MOKA for homeowners, investors, developers and agents: Airbnb and short-term rental management, monthly and weekly rental, corporate housing and renovation across Malaysia.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/solutions-index23.css') }}?v={{ filemtime(public_path('new-theme23/css/solutions-index23.css')) }}">
@endpush

@php
    $by = collect($pages)->keyBy('slug');
    $arrow = '<svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    $models = [
        ['slug' => 'airbnb-management-malaysia', 'chip' => '1 to 7 nights', 'h' => 'Airbnb management, <em>done properly</em>', 'p' => 'Your Airbnb run end to end: photography, listing, pricing, guest messages, check-in, cleaning and reviews.', 'b' => ['Superhost-standard guest care', 'Dynamic nightly pricing', 'Owner portal with every booking']],
        ['slug' => 'short-term-rental-management', 'chip' => 'Nightly and weekly', 'h' => 'Short-term rental for <em>condos and serviced apartments</em>', 'p' => 'Nightly and weekly stays earn more than a long lease when they are run well, across every booking platform at once.', 'b' => ['Airbnb, Booking.com, Agoda and direct', 'One calendar, no double bookings', 'Professional housekeeping between stays']],
        ['slug' => 'monthly-rental', 'chip' => '1 week to a few months', 'h' => 'Monthly and weekly rental, <em>without the long lease</em>', 'p' => 'Furnished stays that fill the gap between hotel rates and a twelve-month tenancy. Marketed, screened and managed.', 'b' => ['Screened guests, deposits handled', 'Higher yield than a fixed tenancy', 'Your unit back when you need it']],
        ['slug' => 'corporate-housing', 'chip' => 'Teams and relocations', 'h' => 'Corporate housing your finance team <em>will thank you for</em>', 'p' => 'Furnished apartments for staff relocations, project teams and extended business travel. One agreement, one monthly invoice.', 'b' => ['Company accounts and consolidated billing', 'Move-in ready, serviced weekly', 'Flexible extensions as projects change']],
    ];
    $aud = [
        ['slug' => 'for-homeowners', 'title' => 'Homeowners', 'p' => 'The condo you moved out of, an inherited unit or a second home. Turn it into managed income.', 'icon' => '<svg viewBox="0 0 24 24"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>'],
        ['slug' => 'for-property-investors', 'title' => 'Property investors', 'p' => 'One studio or a floor of units, operated as a single business with portfolio reporting.', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 19h16"/><path d="M6 16l4-5 4 3 5-7"/><path d="M15 7h4v4"/></svg>'],
        ['slug' => 'for-property-developers', 'title' => 'Developers', 'p' => 'Unsold stock and investor units run by one operator, to one standard, with reporting for buyers.', 'icon' => '<svg viewBox="0 0 24 24"><path d="M4 21V5l7-2v18"/><path d="M11 9l9 2v10"/><path d="M4 21h16"/><path d="M7 8h1M7 12h1M7 16h1M15 14h1M15 18h1"/></svg>'],
        ['slug' => 'for-property-agents', 'title' => 'Agents and agencies', 'p' => 'Refer an owner who wants managed income instead of a tenant. You keep the relationship.', 'icon' => '<svg viewBox="0 0 24 24"><path d="M8 12l-4 3 5 4 3-3"/><path d="M16 12l4 3-5 4-3-3"/><path d="M12 4l-5 5 5 4 5-4z"/></svg>'],
    ];
@endphp

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="sx">
        <section class="sx-hero">
            <div class="sx-in">
                <div class="sx-hero__grid">
                    <div>
                        <div class="sx-eyebrow">Solutions</div>
                        <h1>One team for <em>every way</em> a property can earn</h1>
                        <p class="sx-hero__sub">Nightly, weekly, monthly or corporate. Owned by a family, an investor or a developer, or introduced by an agent. Pick the page that fits you, or start with a free estimate.</p>
                        <div class="sx-actions">
                            <a href="/get/estimate" target="_blank" rel="noopener" class="sx-btn sx-btn--orange">Get a free estimate</a>
                            <a href="/contact" class="sx-btn sx-btn--ghost">Talk to us</a>
                        </div>
                    </div>
                    <div class="sx-hero__art">
                        <picture>
                            <source srcset="{{ asset('new-theme23/images/Asset 1.webp') }}" type="image/webp">
                            <img src="{{ asset('new-theme23/images/Asset 1.png') }}" alt="A MOKA-managed living room" width="460" height="437">
                        </picture>
                        <div class="sx-hero__badge">
                            <svg viewBox="0 0 24 24" fill="#ff6b35"><path d="M12 2l3 6.5 7 .8-5.2 4.8 1.4 7L12 17.6 5.8 21.1l1.4-7L2 9.3l7-.8z"/></svg>
                            <div><b>4.9 / 5</b><span>guest rating</span></div>
                        </div>
                    </div>
                </div>
                <div class="sx-trust">
                    <div><svg viewBox="0 0 24 24"><path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.3 7.2 17.9l.9-5.4L4.2 8.7l5.4-.8z"/></svg><div><b>Superhost and Preferred Host</b>Airbnb and Booking.com</div></div>
                    <div><svg viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M17 7.5c0-1.9-2.2-3-5-3s-5 1.1-5 3 2.2 2.5 5 3 5 1.3 5 3.2-2.2 3.3-5 3.3-5-1.4-5-3.3"/></svg><div><b>Commission only</b>No upfront fee, no lock-in</div></div>
                    <div><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg><div><b>Owner portal</b>Bookings, calendar and revenue, live</div></div>
                    <div><svg viewBox="0 0 24 24"><path d="M12 21s-6-5.3-6-10a6 6 0 0112 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2"/></svg><div><b>Across Malaysia</b>KL, Selangor, Penang, JB, Kota Kinabalu</div></div>
                </div>
            </div>
        </section>

        <section class="sx-sec sx-sec--cream">
            <div class="sx-in">
                <div class="sx-head">
                    <h2>Start with <em>who you are</em></h2>
                    <p>Different owners want different things from a unit. Each page answers the questions we hear most from people like you.</p>
                </div>
                <div class="sx-aud">
                    @foreach($aud as $a)
                    <a href="/solutions/{{ $a['slug'] }}">
                        <div class="sx-ico">{!! $a['icon'] !!}</div>
                        <h3>{{ $a['title'] }}</h3>
                        <p>{{ $a['p'] }}</p>
                        <span class="sx-more">See how MOKA helps {!! $arrow !!}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sx-sec sx-sec--white">
            <div class="sx-in">
                <div class="sx-head">
                    <h2>Then pick <em>how it earns</em></h2>
                    <p>Four ways to let a furnished unit. MOKA runs all of them, and moves a unit between them as the market changes.</p>
                </div>
                <div class="sx-cards">
                    @foreach($models as $m)
                    <a href="/solutions/{{ $m['slug'] }}" class="sx-card">
                        <div class="sx-card__top">
                            <span class="sx-card__eyebrow">{{ $by[$m['slug']]['label'] ?? '' }}</span>
                            <span class="sx-chip">{{ $m['chip'] }}</span>
                        </div>
                        <h3>{!! $m['h'] !!}</h3>
                        <p>{{ $m['p'] }}</p>
                        <ul>@foreach($m['b'] as $b)<li>{{ $b }}</li>@endforeach</ul>
                        <span class="sx-more">Read more {!! $arrow !!}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sx-sec sx-sec--cream">
            <div class="sx-in">
                <div class="sx-head">
                    <h2>Which one <em>fits your unit?</em></h2>
                    <p>A quick comparison. The right answer often changes with the season, which is why MOKA runs all of them.</p>
                </div>
                <div class="sx-table-wrap">
                    <table class="sx-table">
                        <thead><tr><th>Model</th><th>Typical stay</th><th>Who books</th><th>Income</th><th>Your access</th><th>Managed by MOKA</th></tr></thead>
                        <tbody>
                            <tr><th>Nightly <small>Airbnb and short-term</small></th><td>1 to 7 nights</td><td>Holiday and business travellers</td><td>Highest in peak seasons, priced daily</td><td>Block dates for yourself any time</td><td><span class="sx-yes">Yes</span> <a href="/solutions/airbnb-management-malaysia">Airbnb</a> · <a href="/solutions/short-term-rental-management">Short-term</a></td></tr>
                            <tr><th>Weekly and monthly</th><td>1 week to a few months</td><td>Relocations, students, digital nomads</td><td>Steady, above a fixed tenancy</td><td>Unit back between stays</td><td><span class="sx-yes">Yes</span> <a href="/solutions/monthly-rental">Monthly and weekly</a></td></tr>
                            <tr><th>Corporate</th><td>Weeks to a year</td><td>Companies, for staff and project teams</td><td>Contracted, invoiced monthly</td><td>Agreed for the contract term</td><td><span class="sx-yes">Yes</span> <a href="/solutions/corporate-housing">Corporate housing</a></td></tr>
                            <tr class="sx-dim"><th>Long lease <small>for comparison</small></th><td>12 months or more</td><td>One tenant</td><td>Fixed, often the lowest</td><td>None until the lease ends</td><td><span class="sx-no">Not offered</span></td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="sx-swipe">Swipe sideways to see the full comparison.</p>
            </div>
        </section>

        <section class="sx-sec sx-sec--white">
            <div class="sx-in">
                <div class="sx-split">
                    <div>
                        <div class="sx-eyebrow">Renovation for owners</div>
                        <h2>Renovated to earn, <em>built to last</em></h2>
                        <p>A rental unit is furnished for hundreds of guests, not one family. MOKA designs and fits it out for durability, fast turnovers and photographs that book.</p>
                        <a href="/solutions/renovation" class="sx-btn sx-btn--orange">See renovation for owners</a>
                    </div>
                    <picture>
                        <source srcset="{{ asset('new-theme23/images/Asset 48.webp') }}" type="image/webp">
                        <img src="{{ asset('new-theme23/images/Asset 48.png') }}" alt="A renovated MOKA unit" loading="lazy">
                    </picture>
                </div>
            </div>
        </section>

        <section class="sx-sec sx-sec--cream">
            <div class="sx-in">
                <div class="sx-head">
                    <h2>How it works</h2>
                    <p>From first call to first payout, the same three steps for every model.</p>
                </div>
                <div class="sx-steps">
                    <div class="sx-step"><h3>Get your estimate</h3><p>Tell us the unit and the area. We reply with what comparable MOKA units earn and which model suits it.</p></div>
                    <div class="sx-step"><h3>We set it up</h3><p>Styling and photography, listings on every platform, pricing, smart access and housekeeping, all arranged by MOKA.</p></div>
                    <div class="sx-step"><h3>You watch it earn</h3><p>Guests are looked after around the clock. You see every booking and every ringgit in the owner portal, paid monthly.</p></div>
                </div>
            </div>
        </section>

        <section class="sx-cta">
            <h2>See what your unit could earn</h2>
            <p>A free estimate based on real bookings in comparable units. No commitment, no upfront fee.</p>
            <a href="/get/estimate" target="_blank" rel="noopener" class="sx-btn sx-btn--white">Get a quick estimate (Free)</a>
            <small>Takes just 30 seconds.</small>
        </section>
    </div>
@endsection

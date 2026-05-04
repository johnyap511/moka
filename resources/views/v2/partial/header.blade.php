{{--
    MOKA v2 — Header & Navigation
    Usage: @include('v2.partial.header')

    Variables:
      $headerTransparent (bool) — transparent over hero (default: false)
--}}

<?php
$transparent = $headerTransparent ?? false;
$currentPath = request()->path();
$navItems = [
    ['href' => url('https://staymoka.com/'), 'label' => 'Book Now',     'external' => true],
    ['href' => url('/homepage'),             'label' => 'Why MOKA?'],
    ['href' => url('/service'),              'label' => 'Our Services'],
    ['href' => url('/designs'),              'label' => 'Our Designs'],
    ['href' => url('/about'),                'label' => 'About Us'],
];
?>

<header class="moka-header {{ $transparent ? 'is-transparent' : 'is-solid' }}" id="mokaHeader" role="banner">
    <div class="container">
        <div class="header-inner">

            {{-- Logo --}}
            <a href="{{ url('/homepage') }}" class="header-logo" aria-label="MOKA — Home">
                <img src="{{ asset('/new-theme23/images/logo.png') }}"
                     alt="MOKA"
                     width="76"
                     height="46"
                     loading="eager">
            </a>

            {{-- Desktop Nav --}}
            <nav class="header-nav" aria-label="Main navigation">
                @foreach($navItems as $item)
                    @php
                        $isActive = !empty($item['href']) && ('/' . $currentPath === parse_url($item['href'], PHP_URL_PATH));
                    @endphp
                    @if(!empty($item['external']))
                        <a href="{{ $item['href'] }}"
                           class="nav-link"
                           target="_blank"
                           rel="noopener noreferrer">{{ $item['label'] }}</a>
                    @else
                        <a href="{{ $item['href'] }}"
                           class="nav-link {{ $isActive ? 'nav-link--active' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Header Actions --}}
            <div class="header-actions">
                @auth
                    <a href="{{ url('/user/home') }}" class="header-login-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My Account
                    </a>
                @else
                    <button class="header-login-btn" data-open-modal="auth" aria-label="Log in to your account">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Log In
                    </button>
                @endauth

                <a href="{{ url('/get/estimate') }}"
                   class="btn btn-primary btn-sm header-cta"
                   target="_blank"
                   rel="noopener">
                    Get Free Estimate
                </a>

                {{-- Hamburger --}}
                <button class="hamburger" id="hamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>

        </div>
    </div>
</header>

{{-- Mobile Drawer Overlay --}}
<div class="drawer-overlay" id="drawerOverlay" aria-hidden="true"></div>

{{-- Mobile Drawer --}}
<div class="mobile-drawer" id="mobileDrawer" aria-hidden="true" role="dialog" aria-label="Mobile navigation">
    <div class="mobile-drawer-header">
        <a href="{{ url('/homepage') }}" class="mobile-drawer-logo" aria-label="MOKA Home">
            <img src="{{ asset('/new-theme23/images/logo.png') }}" alt="MOKA" width="64" height="38">
        </a>
        <button class="mobile-drawer-close" id="drawerClose" aria-label="Close navigation menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="mobile-drawer-nav" aria-label="Mobile navigation">
        @foreach($navItems as $item)
            @if(!empty($item['external']))
                <a href="{{ $item['href'] }}"
                   class="mobile-nav-link"
                   target="_blank"
                   rel="noopener noreferrer">
                    {{ $item['label'] }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            @else
                <a href="{{ $item['href'] }}" class="mobile-nav-link">{{ $item['label'] }}</a>
            @endif
        @endforeach
    </nav>

    <div class="mobile-drawer-footer">
        @auth
            <a href="{{ url('/user/home') }}" class="btn btn-outline-teal" style="width:100%; justify-content:center;">My Account</a>
        @else
            <button class="btn btn-outline-teal" data-open-modal="auth" style="width:100%; justify-content:center;">Log In</button>
        @endauth
        <a href="{{ url('/get/estimate') }}"
           class="btn btn-primary"
           style="width:100%; justify-content:center;"
           target="_blank"
           rel="noopener">
            Get Free Estimate
        </a>
    </div>
</div>

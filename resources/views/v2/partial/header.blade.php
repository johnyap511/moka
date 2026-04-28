{{--
    MOKA v2 — Header & Navigation
    Usage: @include('v2.partial.header')

    Variables:
      $headerTransparent (bool) — transparent over hero (default: false)
--}}

<?php
$transparent  = $headerTransparent ?? false;
$currentPath  = request()->path();
$navItems = [
    ['href' => url('https://staymoka.com/'), 'label' => 'Book Now', 'external' => true],
    ['href' => url('/homepage'),             'label' => 'Why MOKA?'],
    ['href' => url('/service'),              'label' => 'Our Services'],
    ['href' => url('/designs'),              'label' => 'Our Designs'],
    ['href' => url('/about'),                'label' => 'About Us'],
];
?>

<header class="moka-header {{ $transparent ? 'transparent' : 'solid' }}" id="mokaHeader">
    <div class="container">
        <div class="header-inner">

            <!-- Logo -->
            <a href="{{ url('/homepage') }}" class="header-logo" aria-label="MOKA Home">
                <img src="{{ asset('/new-theme23/images/logo.png') }}" alt="MOKA" width="70" height="42">
            </a>

            <!-- Desktop Nav -->
            <nav class="header-nav" aria-label="Main navigation">
                @foreach($navItems as $item)
                    @if(!empty($item['external']))
                        <a href="{{ $item['href'] }}"
                           class="nav-link"
                           target="_blank"
                           rel="noopener">{{ $item['label'] }}</a>
                    @else
                        <a href="{{ $item['href'] }}"
                           class="nav-link {{ ('/' . $currentPath === parse_url($item['href'], PHP_URL_PATH)) ? 'active' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                {{-- Login button (opens modal) --}}
                @auth
                    <a href="{{ url('/user/home') }}" class="header-login-btn">My Account</a>
                @else
                    <button class="header-login-btn" data-open-modal="auth" aria-label="Log in">
                        Log In
                    </button>
                @endauth

                {{-- CTA --}}
                <a href="{{ url('/get/estimate') }}" class="btn header-cta btn-sm" target="_blank">
                    Get Free Estimate
                </a>

                {{-- Hamburger --}}
                <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

        </div>
    </div>
</header>

<!-- Mobile Drawer -->
<div class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
    @foreach($navItems as $item)
        @if(!empty($item['external']))
            <a href="{{ $item['href'] }}"
               class="mobile-nav-link"
               target="_blank" rel="noopener">{{ $item['label'] }}</a>
        @else
            <a href="{{ $item['href'] }}" class="mobile-nav-link">{{ $item['label'] }}</a>
        @endif
    @endforeach

    <div class="mobile-drawer-footer">
        @auth
            <a href="{{ url('/user/home') }}" class="btn btn-outline">My Account</a>
        @else
            <button class="btn btn-outline" data-open-modal="auth">Log In</button>
        @endauth
        <a href="{{ url('/get/estimate') }}" class="btn btn-primary" target="_blank">
            Get Free Estimate
        </a>
    </div>
</div>

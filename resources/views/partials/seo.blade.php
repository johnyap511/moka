{{--
    Central SEO block, included once from auth/newTheme/layout.blade.php so every
    public page gets a full set of meta tags.

    Pages override any of these by defining the matching section, e.g.

        @section('seo_title', 'About MOKA | Property Management Malaysia')
        @section('seo_description', '...')
        @section('seo_robots', 'noindex,follow')

    Anything not overridden falls back to the site-wide defaults below.
--}}
@php
    // Legacy section names (title / meta_description / og_*) are still used by the
    // v2 views, so honour them as a fallback before dropping to the site default.
    $legacyTitle = trim($__env->yieldContent('title'));
    $legacyDesc  = trim($__env->yieldContent('meta_description'));

    $seoTitle       = trim($__env->yieldContent('seo_title')) ?: $legacyTitle
        ?: 'Airbnb & Short-Stay Property Management in Malaysia | MOKA';
    $seoDescription = trim($__env->yieldContent('seo_description')) ?: $legacyDesc
        ?: 'MOKA manages your Airbnb and short-stay property end to end — renovation, listing, professional photography, price optimisation, guest vetting and housekeeping. Find out what your property could earn.';
    $seoCanonical   = trim($__env->yieldContent('seo_canonical')) ?: url()->current();
    $seoRobots      = trim($__env->yieldContent('seo_robots'))    ?: 'index,follow,max-image-preview:large';
    $seoImage       = trim($__env->yieldContent('seo_image'))     ?: asset('images/layout/og-cover.jpg');
    $seoType        = trim($__env->yieldContent('seo_type'))      ?: 'website';

    $ogTitle = trim($__env->yieldContent('og_title')) ?: $seoTitle;
    $ogDesc  = trim($__env->yieldContent('og_description')) ?: $seoDescription;
@endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    {{-- Open Graph — drives WhatsApp, Facebook and LinkedIn link previews --}}
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:site_name" content="MOKA">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_MY">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    {{-- Organisation + site identity. Kept on every page so search engines can
         resolve the brand regardless of which page they crawl first. --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'Organization',
                '@id'         => url('/') . '/#organization',
                'name'        => 'MOKA',
                'url'         => url('/'),
                'logo'        => asset('images/layout/logo3.svg'),
                'image'       => asset('images/layout/og-cover.jpg'),
                'description' => 'Airbnb and short-stay property management in Malaysia.',
                'areaServed'  => ['@type' => 'Country', 'name' => 'Malaysia'],
            ],
            [
                '@type'      => 'WebSite',
                '@id'        => url('/') . '/#website',
                'url'        => url('/'),
                'name'       => 'MOKA',
                'publisher'  => ['@id' => url('/') . '/#organization'],
                'inLanguage' => 'en-MY',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- Page-specific structured data (FAQ, Breadcrumb, Product, ...) --}}
    @stack('schema')

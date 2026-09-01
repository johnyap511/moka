<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#003d3c">
    <meta name="msapplication-TileColor" content="#003d3c">

    @include('partials.seo')

    {{-- Favicon --}}
    <link rel="shortcut icon"    href="{{ asset('images/layout/fav.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/layout/logo3.svg') }}">

    {{-- Font Preconnect (must come before stylesheet) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Font Preloads for critical weights --}}
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=DM+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap"
          media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap">
    </noscript>

    {{-- MOKA v2 CSS — order matters --}}
    <link rel="stylesheet" href="{{ asset('/moka-v2/css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('/moka-v2/css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('/moka-v2/css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('/moka-v2/css/listings.css') }}">
    <link rel="stylesheet" href="{{ asset('/moka-v2/css/pages.css') }}">

    {{-- Toastr flash notifications --}}
    <link rel="stylesheet" href="{{ asset('/plugins/toastr/toastr.css') }}">

    @yield('head')
</head>
<body>

{{-- ══════════════════════════════════════════
     HEADER
══════════════════════════════════════════ --}}
@include('v2.partial.header')

{{-- ══════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════ --}}
<main id="main-content">
    @yield('content')
</main>

{{-- ══════════════════════════════════════════
     FOOTER
══════════════════════════════════════════ --}}
@include('v2.partial.footer')

{{-- ══════════════════════════════════════════
     AUTH MODAL
══════════════════════════════════════════ --}}
@include('v2.partial.auth-modal')

{{-- ══════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════ --}}
<script src="{{ asset('/new-theme23/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('/moka-v2/js/moka.js') }}"></script>

<script>
$(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @elseif(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
    @foreach ($errors->all() as $error)
        toastr.error('{{ $error }}');
    @endforeach
});
</script>

@yield('scripts')
</body>
</html>

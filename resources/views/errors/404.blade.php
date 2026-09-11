@extends('auth.newTheme.layout')
@section('seo_title', 'Page not found | MOKA')
@section('seo_robots', 'noindex,follow')
@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <style>.nf{background:var(--blog-cream);padding:56px 20px 80px}.nf__in{max-width:760px;margin:0 auto}.nf__grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-top:8px}.nf__grid a{display:block;background:#fff;border:1px solid var(--blog-rule);border-radius:14px;padding:18px 20px;color:var(--blog-teal);font-family:SemiBold;text-decoration:none}.nf__grid a small{display:block;font-family:SansPro_Regular,sans-serif;color:var(--blog-muted);font-size:14px;margin-top:4px}.nf__grid a:hover{border-color:var(--blog-orange)}@media(max-width:600px){.nf__grid{grid-template-columns:1fr}}</style>
@endpush
@section('content')
    @include('auth.newTheme.partials.header')
    <div class="blog-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Error 404</div>
            <h1>That page is not here</h1>
            <p class="blog-hero__meta">The link may be old, or the address mistyped. Here is the way back.</p>
        </div>
    </div>
    <div class="nf"><div class="nf__in"><div class="nf__grid">
        <a href="/">Homepage<small>Homestay and Airbnb management in Malaysia</small></a>
        <a href="/solutions">Solutions<small>For homeowners, investors, developers and agents</small></a>
        <a href="/designs">Renovation and interior design<small>Innspace, SkyWorld Solution+ panel renovator</small></a>
        <a href="/blog">Blog<small>Advice for property owners</small></a>
        <a href="/get/estimate">Free income estimate<small>What your unit could earn</small></a>
        <a href="/contact">Contact us<small>hello@homemoka.com · +603 6789 2288</small></a>
    </div></div></div>
@endsection

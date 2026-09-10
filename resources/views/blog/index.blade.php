@extends('auth.newTheme.layout')

@section('seo_title', 'MOKA Blog | Airbnb Management & Renovation Advice for Malaysian Owners')
@section('seo_description', 'Practical guides for Malaysian property owners on short-stay hosting, Airbnb management, renovation and furnishing — written by the MOKA team.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
@endpush

@push('schema')
    <script type="application/ld+json">
    {!! json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'Blog',
        'name'            => 'MOKA Blog',
        'description'     => 'Short-stay hosting and renovation advice for Malaysian property owners.',
        'url'             => route('blog.index'),
        'inLanguage'      => 'en-MY',
        'publisher'       => ['@id' => url('/') . '/#organization'],
        'blogPost'        => $posts->map(function ($post) {
            return [
                '@type'         => 'BlogPosting',
                'headline'      => $post['heading'],
                'description'   => $post['description'],
                'datePublished' => $post['published'],
                'image'         => asset($post['image']),
                'url'           => route('blog.show', $post['slug']),
            ];
        })->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@php
    $thumb = fn ($img) => asset(preg_replace('/\.jpg$/', '-thumb.webp', $img));
    $fallback = fn ($img) => asset(preg_replace('/\.jpg$/', '-thumb.jpg', $img));
    $featured = $posts->first();
    $rest = $posts->slice(1)->values();
    $topics = $posts->pluck('topic')->filter()->unique()->values();
@endphp

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero blog-hero--index">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">MOKA Blog</div>
            <h1>Advice for property owners</h1>
            <p class="blog-hero__meta">Short-stay hosting, renovation and the rules that shape both. What we have learned managing properties across Malaysia.</p>
        </div>
    </div>

    <div class="blog-list">
        <div class="blog-list__inner blog-list__inner--wide">

            @if($featured)
            <a href="{{ route('blog.show', $featured['slug']) }}" class="blog-feature" data-topic="{{ $featured['topic'] ?? '' }}">
                <div class="blog-feature__media">
                    <picture>
                        <source srcset="{{ asset($featured['image']) }}" type="image/jpeg">
                        <img src="{{ asset($featured['image']) }}" alt="" loading="eager" width="1600" height="1000">
                    </picture>
                    <span class="blog-tag">Latest</span>
                </div>
                <div class="blog-feature__body">
                    <div class="blog-card__topic">{{ $featured['topic'] ?? 'Guide' }}</div>
                    <h2>{{ $featured['heading'] }}</h2>
                    <p>{{ $featured['description'] }}</p>
                    <span class="blog-card__meta"><time datetime="{{ $featured['published'] }}">{{ \Carbon\Carbon::parse($featured['published'])->format('j M Y') }}</time> · {{ $featured['read_time'] }} min read</span>
                    <span class="blog-readmore">Read the article <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>
            </a>
            @endif

            <div class="blog-filter" role="tablist" aria-label="Topics">
                <button type="button" class="active" data-filter="">All articles</button>
                @foreach($topics as $t)
                    <button type="button" data-filter="{{ $t }}">{{ $t }}</button>
                @endforeach
            </div>

            <div class="blog-grid blog-grid--rich" id="blogGrid">
                @foreach ($rest as $post)
                    <a href="{{ route('blog.show', $post['slug']) }}" class="blog-card blog-card--rich" data-topic="{{ $post['topic'] ?? '' }}">
                        <div class="blog-card__media">
                            <picture>
                                <source srcset="{{ $thumb($post['image']) }}" type="image/webp">
                                <img src="{{ $fallback($post['image']) }}" alt="" loading="lazy" width="720" height="540">
                            </picture>
                        </div>
                        <div class="blog-card__body">
                            <div class="blog-card__topic">{{ $post['topic'] ?? 'Guide' }}</div>
                            <h2>{{ $post['heading'] }}</h2>
                            <p>{{ $post['description'] }}</p>
                            <span class="blog-card__meta"><time datetime="{{ $post['published'] }}">{{ \Carbon\Carbon::parse($post['published'])->format('j M Y') }}</time> · {{ $post['read_time'] }} min read</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <p class="blog-empty" id="blogEmpty" hidden>No other articles on this topic yet.</p>

            <div class="blog-cta blog-cta--index">
                <div>
                    <h2>Own a unit and wondering what it could earn?</h2>
                    <p>A free estimate based on real bookings in comparable units. No commitment, no upfront fee.</p>
                </div>
                <a href="/get/estimate" target="_blank" rel="noopener" class="blog-cta__btn">Get a free estimate</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var btns = document.querySelectorAll('.blog-filter button'), cards = document.querySelectorAll('#blogGrid .blog-card'), feature = document.querySelector('.blog-feature'), empty = document.getElementById('blogEmpty');
    btns.forEach(function (b) { b.addEventListener('click', function () {
        btns.forEach(function (x) { x.classList.remove('active'); }); b.classList.add('active');
        var f = b.dataset.filter, n = 0;
        cards.forEach(function (c) { var on = !f || c.dataset.topic === f; c.hidden = !on; if (on) n++; });
        if (feature) feature.hidden = !!f && feature.dataset.topic !== f;
        empty.hidden = n > 0 || (feature && !feature.hidden);
    }); });
})();
</script>
@endpush

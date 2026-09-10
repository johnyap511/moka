{{--
    Shared chrome for every blog post. Posts extend this and supply @section('article').
    All SEO values come from the entry in config/blog.php, so a post's Blade file
    only ever contains prose.
--}}
@extends('auth.newTheme.layout')

@section('seo_title', $post['title'])
@section('seo_description', $post['description'])
@section('seo_type', 'article')
@section('seo_image', asset($post['image']))

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
@endpush

@push('schema')
    <script type="application/ld+json">
    {!! json_encode([
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => $post['heading'],
        'description'      => $post['description'],
        'datePublished'    => $post['published'],
        'dateModified'     => $post['updated'],
        'image'            => asset($post['image']),
        'inLanguage'       => 'en-MY',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('blog.show', $post['slug'])],
        'author'           => ['@type' => 'Organization', 'name' => 'MOKA', '@id' => url('/') . '/#organization'],
        'publisher'        => ['@id' => url('/') . '/#organization'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post['heading']],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@php
    $topic = $post['topic'] ?? 'Guide';
    $figures = [
        'Renovation'  => [['new-theme23/images/projects/skyvogue/2.jpg', 'Living room by Innspace at SkyVogue'], ['new-theme23/images/projects/the-valley/7.jpg', 'Kitchen by Innspace at The Valley']],
        'Hosting'     => [['new-theme23/images/projects/the-valley/2.jpg', 'A MOKA-managed unit at The Valley'], ['new-theme23/images/projects/skyawani-4/2.jpg', 'Living area, Sky Awani 4']],
        'Regulations' => [['new-theme23/images/projects/skyawani-4/6.jpg', 'Bedroom, Sky Awani 4'], ['new-theme23/images/projects/skyawani-4/1.jpg', 'Living and dining, Sky Awani 4']],
    ][$topic] ?? [['new-theme23/images/projects/the-valley/1.jpg', 'A MOKA-managed unit at The Valley']];
    $figures = array_values(array_filter($figures, fn ($f) => $f[0] !== $post['image']));
    $share = rawurlencode($post['heading'] . ' ' . route('blog.show', $post['slug']));
    $webp = fn ($img) => asset(preg_replace('/\.jpg$/', '.webp', $img));
@endphp

@section('content')
    @include('auth.newTheme.partials.header')
    <div class="blog-progress" id="blogProgress"></div>

    <article>
        <div class="blog-hero blog-hero--article">
            <div class="blog-hero__inner blog-hero__inner--article">
                <div class="blog-crumbs"><a href="{{ url('/') }}">Home</a><span>/</span><a href="{{ route('blog.index') }}">Blog</a><span>/</span><em>{{ $topic }}</em></div>
                <h1>{{ $post['heading'] }}</h1>
                <p class="blog-hero__meta">
                    <span class="blog-hero__topic">{{ $topic }}</span>
                    <time datetime="{{ $post['published'] }}">{{ \Carbon\Carbon::parse($post['published'])->format('j F Y') }}</time>
                    · {{ $post['read_time'] }} min read · by the MOKA team
                </p>
            </div>
        </div>
        <div class="blog-cover">
            <picture>
                <source srcset="{{ $webp($post['image']) }}" type="image/webp">
                <img src="{{ asset($post['image']) }}" alt="" width="1600" height="1000" loading="eager">
            </picture>
        </div>

        <div class="blog-body blog-body--article">
            <div class="blog-layout">
                <div class="blog-body__inner blog-body__inner--article" id="blogArticle">
                    @yield('article')

                    <div class="blog-share">
                        <span>Share this article</span>
                        <a href="https://wa.me/?text={{ $share }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><svg viewBox="0 0 24 24"><path d="M20 3.9A9.9 9.9 0 0 0 4.4 15.8L3 21l5.3-1.4A9.9 9.9 0 1 0 20 3.9zm-8 15.6a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1 1 12 19.5zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.6 4c1.7.7 2.1.6 2.8.5a2.4 2.4 0 0 0 1.6-1.1 2 2 0 0 0 .1-1.1c0-.1-.2-.2-.5-.3z"/></svg></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode(route('blog.show', $post['slug'])) }}" target="_blank" rel="noopener" aria-label="Share on Facebook"><svg viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.6 1.6-1.6h1.7V4.4c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.2v2.3H7.4V14h2.8v8h3.3z"/></svg></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode(route('blog.show', $post['slug'])) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><svg viewBox="0 0 24 24"><path d="M20.4 2H3.6A1.6 1.6 0 002 3.6v16.8A1.6 1.6 0 003.6 22h16.8a1.6 1.6 0 001.6-1.6V3.6A1.6 1.6 0 0020.4 2zM8 19H5V9.5h3V19zM6.5 8.2a1.7 1.7 0 110-3.4 1.7 1.7 0 010 3.4zM19 19h-3v-4.6c0-1.1 0-2.5-1.5-2.5S12.7 13 12.7 14.3V19h-3V9.5h2.9v1.3a3.2 3.2 0 012.8-1.5c3 0 3.6 2 3.6 4.6V19z"/></svg></a>
                        <button type="button" id="blogCopy" aria-label="Copy link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg></button>
                    </div>

                    <div class="blog-author">
                        <img src="{{ asset('images/app/icon-192.png') }}" alt="MOKA" width="56" height="56">
                        <div><b>Written by the MOKA team</b><span>Malaysia's short-stay property management company. Superhost and Preferred Host, managing units in KL, Selangor, Penang, Johor Bahru and Kota Kinabalu.</span></div>
                    </div>

                    {{-- Posts about renovation need a different call to action from
                         posts about hosting, so each entry in config/blog.php may
                         override the three cta_* keys. --}}
                    <div class="blog-cta">
                        <h2>{{ $post['cta_heading'] ?? 'Find out what your property could earn' }}</h2>
                        <p>{{ $post['cta_body'] ?? 'A free, no-obligation estimate from the MOKA team.' }}</p>
                        <a href="{{ $post['cta_url'] ?? '/get/estimate' }}" class="blog-cta__btn">
                            {{ $post['cta_label'] ?? 'Get a quick estimate' }}
                        </a>
                    </div>
                </div>

                <aside class="blog-aside">
                    <div class="blog-toc" id="blogToc" hidden>
                        <div class="blog-toc__title">In this article</div>
                        <ol></ol>
                    </div>
                    <div class="blog-aside__card">
                        <div class="blog-card__topic">{{ $post['cta_heading'] ?? 'Own a unit?' }}</div>
                        <p>{{ $post['cta_body'] ?? 'See what your unit could earn with MOKA. Free, no obligation.' }}</p>
                        <a href="{{ $post['cta_url'] ?? '/get/estimate' }}" class="blog-cta__btn">{{ $post['cta_label'] ?? 'Get a quick estimate' }}</a>
                    </div>
                </aside>
            </div>

            @if ($related->isNotEmpty())
                <div class="blog-related blog-related--wide">
                    <h2>More for property owners</h2>
                    <div class="blog-grid blog-grid--rich">
                        @foreach ($related as $item)
                            <a href="{{ route('blog.show', $item['slug']) }}" class="blog-card blog-card--rich">
                                <div class="blog-card__media">
                                    <picture>
                                        <source srcset="{{ asset(preg_replace('/\.jpg$/', '-thumb.webp', $item['image'])) }}" type="image/webp">
                                        <img src="{{ asset(preg_replace('/\.jpg$/', '-thumb.jpg', $item['image'])) }}" alt="" loading="lazy" width="720" height="540">
                                    </picture>
                                </div>
                                <div class="blog-card__body">
                                    <div class="blog-card__topic">{{ $item['topic'] ?? 'Guide' }}</div>
                                    <h3>{{ $item['heading'] }}</h3>
                                    <p>{{ $item['description'] }}</p>
                                    <span class="blog-card__meta">{{ $item['read_time'] }} min read</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>
@endsection

@push('scripts')
<script>
(function () {
    var art = document.getElementById('blogArticle'), h2s = Array.prototype.slice.call(art.querySelectorAll('h2')).filter(function (h) { return !h.closest('.blog-cta'); });
    var toc = document.getElementById('blogToc'), ol = toc.querySelector('ol');
    if (h2s.length >= 3) {
        h2s.forEach(function (h, i) { if (!h.id) h.id = 'section-' + (i + 1); var li = document.createElement('li'); var a = document.createElement('a'); a.href = '#' + h.id; a.textContent = h.textContent; li.appendChild(a); ol.appendChild(li); });
        toc.hidden = false;
    }
    var figs = {!! json_encode(array_map(fn ($f) => ['src' => asset($f[0]), 'webp' => $webp($f[0]), 'cap' => $f[1]], $figures)) !!};
    [1, 3].forEach(function (pos, i) {
        var h = h2s[pos], f = figs[i]; if (!h || !f) return;
        var fig = document.createElement('figure'); fig.className = 'blog-figure';
        fig.innerHTML = '<picture><source srcset="' + f.webp + '" type="image/webp"><img src="' + f.src + '" alt="' + f.cap + '" loading="lazy" width="1600" height="1000"></picture><figcaption>' + f.cap + '</figcaption>';
        h.parentNode.insertBefore(fig, h);
    });
    var bar = document.getElementById('blogProgress');
    function prog() { var r = art.getBoundingClientRect(), total = r.height - innerHeight, done = Math.min(Math.max(-r.top, 0), Math.max(total, 1)); bar.style.width = (total > 0 ? done / total * 100 : 100) + '%'; }
    addEventListener('scroll', prog, { passive: true }); prog();
    var c = document.getElementById('blogCopy');
    c.addEventListener('click', function () { navigator.clipboard.writeText(location.href).then(function () { c.classList.add('done'); setTimeout(function () { c.classList.remove('done'); }, 1500); }); });
})();
</script>
@endpush

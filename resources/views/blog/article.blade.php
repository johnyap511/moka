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
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}">
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

@section('content')
    @include('auth.newTheme.partials.header')

    <article>
        <div class="blog-hero">
            <div class="blog-hero__inner">
                <div class="blog-hero__eyebrow">
                    <a href="{{ route('blog.index') }}" style="color:inherit;text-decoration:none;">MOKA Blog</a>
                </div>
                <h1>{{ $post['heading'] }}</h1>
                <p class="blog-hero__meta">
                    <time datetime="{{ $post['published'] }}">
                        {{ \Carbon\Carbon::parse($post['published'])->format('j F Y') }}
                    </time>
                    · {{ $post['read_time'] }} min read
                </p>
            </div>
        </div>

        <div class="blog-body">
            <div class="blog-body__inner">
                @yield('article')

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

                @if ($related->isNotEmpty())
                    <div class="blog-related">
                        <h2>More for property owners</h2>
                        <div class="blog-grid">
                            @foreach ($related as $item)
                                <div class="blog-card">
                                    <h3><a href="{{ route('blog.show', $item['slug']) }}">{{ $item['heading'] }}</a></h3>
                                    <p>{{ $item['description'] }}</p>
                                    <span class="blog-card__meta">{{ $item['read_time'] }} min read</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </article>
@endsection

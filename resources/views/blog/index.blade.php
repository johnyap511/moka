@extends('auth.newTheme.layout')

@section('seo_title', 'MOKA Blog | Airbnb Management & Renovation Advice for Malaysian Owners')
@section('seo_description', 'Practical guides for Malaysian property owners on short-stay hosting, Airbnb management, renovation and furnishing — written by the MOKA team.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}">
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
                'url'           => route('blog.show', $post['slug']),
            ];
        })->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">MOKA Blog</div>
            <h1>Advice for property owners</h1>
            <p class="blog-hero__meta">
                Short-stay hosting, renovation and furnishing — what we have learned managing
                properties across Malaysia.
            </p>
        </div>
    </div>

    <div class="blog-list">
        <div class="blog-list__inner">
            <div class="blog-grid">
                @foreach ($posts as $post)
                    <div class="blog-card">
                        <h2><a href="{{ route('blog.show', $post['slug']) }}">{{ $post['heading'] }}</a></h2>
                        <p>{{ $post['description'] }}</p>
                        <span class="blog-card__meta">
                            <time datetime="{{ $post['published'] }}">
                                {{ \Carbon\Carbon::parse($post['published'])->format('j M Y') }}
                            </time>
                            · {{ $post['read_time'] }} min read
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

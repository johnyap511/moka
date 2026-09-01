<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticPages as $page)
    <url>
        <loc>{{ url($page['path']) }}</loc>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@foreach ($listings as $listing)
    <url>
        <loc>{{ url('/listing/' . rawurlencode($listing['slug'])) }}</loc>
@if ($listing['lastmod'])
        <lastmod>{{ $listing['lastmod'] }}</lastmod>
@endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach ($posts as $post)
    <url>
        <loc>{{ route('blog.show', $post['slug']) }}</loc>
        <lastmod>{{ $post['lastmod'] }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
@endforeach
</urlset>

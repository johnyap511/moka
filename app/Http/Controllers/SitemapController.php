<?php

namespace App\Http\Controllers;

use App\Listing;

class SitemapController extends Controller
{
    /**
     * Public marketing pages, with the relative weight we want to signal.
     *
     * `/homepage` is deliberately absent: it renders the same page as `/` and
     * canonicalises to it, so listing both would split ranking signals.
     */
    private const STATIC_PAGES = [
        ['path' => '/',             'priority' => '1.0', 'changefreq' => 'weekly',  'lastmod' => '2026-09-11'],
        ['path' => '/service',      'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-09-10'],
        ['path' => '/solutions',    'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-09-11'],
        ['path' => '/blog',         'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => '2026-09-11'],
        ['path' => '/get/estimate', 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => '2026-09-08'],
        ['path' => '/about',        'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => '2026-09-10'],
        ['path' => '/designs',      'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => '2026-09-11'],
        ['path' => '/contact',      'priority' => '0.6', 'changefreq' => 'yearly',  'lastmod' => '2026-09-08'],
        ['path' => '/policy',       'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => '2026-09-08'],
        ['path' => '/terms',        'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => '2026-09-08'],
    ];

    public function index()
    {
        // Only live listings, and only those addressable by the /listing/{key}
        // route — `key` is nullable, and propertyDetail() also accepts `name`.
        // Unit pages are not published since the booking engine was retired (8 Sep 2026).
        $listings = Listing::whereRaw('1 = 0')
            ->where('user_id', '<>', 4475)   // company extra rooms are not pages for Google
            ->select('key', 'name', 'updated_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($listing) {
                return [
                    'slug'    => $listing->key ?: $listing->name,
                    'lastmod' => optional($listing->updated_at)->toAtomString(),
                ];
            })
            ->filter(function ($listing) {
                return ! empty($listing['slug']);
            });

        // The XML declaration is prepended here rather than in the Blade view:
        // Blade skips any line containing a literal `<?`, treating it as a raw
        // PHP tag, so the declaration would pass through uncompiled.
        $solutions = collect(config('solutions.pages', []))->map(fn ($p) => ['path' => '/solutions/' . $p['slug'], 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => \Carbon\Carbon::parse($p['updated'])->toAtomString()]);
        $posts = collect(config('blog.posts', []))->map(function ($post) {
            return [
                'slug'    => $post['slug'],
                'lastmod' => $post['updated'],
            ];
        });

        $body = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . view('sitemap', [
            'staticPages' => self::STATIC_PAGES,
            'listings'    => $listings,
            'posts'       => $posts,
            'solutions'   => $solutions,
        ])->render();

        return response($body)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

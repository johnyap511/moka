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
        ['path' => '/',             'priority' => '1.0', 'changefreq' => 'weekly'],
        ['path' => '/service',      'priority' => '0.9', 'changefreq' => 'monthly'],
        ['path' => '/get/estimate', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['path' => '/about',        'priority' => '0.7', 'changefreq' => 'monthly'],
        ['path' => '/designs',      'priority' => '0.7', 'changefreq' => 'monthly'],
        ['path' => '/contact',      'priority' => '0.6', 'changefreq' => 'yearly'],
        ['path' => '/policy',       'priority' => '0.3', 'changefreq' => 'yearly'],
        ['path' => '/terms',        'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    public function index()
    {
        // Only live listings, and only those addressable by the /listing/{key}
        // route — `key` is nullable, and propertyDetail() also accepts `name`.
        $listings = Listing::where('status', 1)
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
        $body = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . view('sitemap', [
            'staticPages' => self::STATIC_PAGES,
            'listings'    => $listings,
        ])->render();

        return response($body)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * /llms.txt and /llms-full.txt: a plain-text map of the site for AI assistants
 * (the llms.txt convention). Facts come from config/seo.php, pages from the
 * solutions and blog configs, so they never drift from what the site says.
 */
class LlmsController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!RobotsController::isCanonicalHost()) {
            abort(404);
        }
        $full = str_ends_with($request->path(), 'llms-full.txt');
        $facts = config('seo.facts', []);
        $out = ["# MOKA (homemoka.com)", "", "> " . config('seo.summary'), "", "## Key facts", ""];
        foreach ($facts as $f) {
            $out[] = "- " . $f;
        }
        $out[] = "";
        $out[] = "## Main pages";
        $out[] = "";
        foreach ([['/', 'Home: what MOKA does for property owners'], ['/service', 'Services: listing, pricing, guests, housekeeping, maintenance, renovation'], ['/solutions', 'Solutions by owner type, stay type and city'], ['/designs', 'Renovation and interior design by Innspace'], ['/about', 'About MOKA'], ['/get/estimate', 'Free earning estimate for a unit'], ['/contact', 'Contact']] as [$path, $desc]) {
            $out[] = "- [" . $desc . "](" . url($path) . ")";
        }
        $out[] = "";
        $out[] = "## Solutions";
        $out[] = "";
        foreach (config('solutions.pages', []) as $p) {
            $out[] = "- [" . strip_tags($p['h1']) . "](" . url('/solutions/' . $p['slug']) . "): " . $p['description'];
            if ($full) {
                foreach ($p['sections'] ?? [] as $s) {
                    $out[] = "  - " . $s['h2'] . ": " . ($s['p'] ?? '') . (!empty($s['bullets']) ? ' ' . implode('; ', $s['bullets']) : '');
                }
                foreach ($p['faqs'] ?? [] as $f) {
                    $out[] = "  - Q: " . $f['q'] . " A: " . $f['a'];
                }
            }
        }
        $out[] = "";
        $out[] = "## Blog";
        $out[] = "";
        $posts = collect(config('blog.posts', []))->filter(fn ($p) => $p['published'] <= now()->toDateString())->sortByDesc('published');
        foreach ($posts as $p) {
            $out[] = "- [" . $p['heading'] . "](" . url('/blog/' . $p['slug']) . ") (" . $p['published'] . "): " . $p['description'];
            if ($full && !empty($p['faq'])) {
                foreach ($p['faq'] as [$q, $a]) {
                    $out[] = "  - Q: " . $q . " A: " . $a;
                }
            }
        }
        $out[] = "";
        $out[] = "## Contact";
        $out[] = "";
        $out[] = "- Email: hello@homemoka.com";
        $out[] = "- Phone: +60 3 6789 2288";
        $out[] = "- Owner estimate: " . url('/get/estimate');

        return response(implode("\n", $out) . "\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}

<?php

namespace App\Http\Controllers;

class RobotsController extends Controller
{
    /**
     * robots.txt, served by the app rather than as a file in public/.
     *
     * Staging runs the same code on a public IP, so a static file would tell
     * crawlers to index it exactly as production — a full duplicate of the site
     * competing with homemoka.com for its own keywords. nginx serves a real file
     * before Laravel is reached, so the file had to go for this to be decidable
     * per environment.
     */
    public function __invoke()
    {
        $body = self::isCanonicalHost()
            ? $this->production()
            : $this->nonProduction();

        return response($body)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Whether this request is being served by the real site.
     *
     * Host, not APP_ENV: staging runs with APP_ENV=production, so the
     * environment name cannot tell them apart.
     */
    public static function isCanonicalHost(): bool
    {
        $canonical = strtolower(trim((string) config('seo.canonical_host')));

        if ($canonical === '') {
            return true;
        }

        $host = strtolower((string) request()->getHost());

        return $host === $canonical || $host === 'www.' . $canonical;
    }

    private function nonProduction(): string
    {
        return <<<TXT
        # Not production. Nothing here should be indexed — this host runs a copy
        # of the live site and would otherwise compete with it in search.
        User-agent: *
        Disallow: /
        TXT;
    }

    private function production(): string
    {
        $sitemap = url('/sitemap.xml');

        return <<<TXT
        # https://homemoka.com/robots.txt

        # ---------------------------------------------------------------------
        # Search engines: crawl the public marketing and listing pages, stay out
        # of the authenticated app. Nothing behind /admin, /owner or /user is
        # useful in an index, and crawling it only wastes crawl budget.
        # ---------------------------------------------------------------------
        User-agent: *
        Content-Signal: search=yes,ai-train=no,use=reference
        Allow: /
        Disallow: /admin
        Disallow: /owner
        Disallow: /user
        Disallow: /login
        Disallow: /register
        Disallow: /password
        Disallow: /payment
        Disallow: /logout
        Disallow: /announcement
        Disallow: /language/
        Disallow: /*?*modal=

        # ---------------------------------------------------------------------
        # AI crawlers: same stance as staymoka.com — content may be used to
        # answer questions with attribution, but not to train models.
        # ---------------------------------------------------------------------
        User-agent: Amazonbot
        Disallow: /

        User-agent: Applebot-Extended
        Disallow: /

        User-agent: Bytespider
        Disallow: /

        User-agent: CCBot
        Disallow: /

        User-agent: ClaudeBot
        Disallow: /

        User-agent: Google-Extended
        Disallow: /

        User-agent: GPTBot
        Disallow: /

        User-agent: meta-externalagent
        Disallow: /

        Sitemap: {$sitemap}
        TXT;
    }
}

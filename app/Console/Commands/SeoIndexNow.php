<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Submit URLs to IndexNow (Bing, Yandex, Seznam, Naver and, through Bing, the
 * answer engines that use its index: ChatGPT search, Copilot, DuckDuckGo).
 * With no URLs given it submits blog posts published today and solutions
 * updated today; the scheduler runs that nightly.
 */
class SeoIndexNow extends Command
{
    protected $signature = 'seo:indexnow {urls?* : full URLs; default = pages published or updated today}';
    protected $description = 'Submit new or updated pages to IndexNow';

    public function handle(): int
    {
        $key = config('seo.indexnow_key');
        if (!$key) {
            $this->error('seo.indexnow_key not set');
            return 1;
        }
        $urls = $this->argument('urls');
        if (!$urls) {
            $today = now()->toDateString();
            foreach (config('blog.posts', []) as $p) {
                if ($p['published'] === $today || $p['updated'] === $today) {
                    $urls[] = url('/blog/' . $p['slug']);
                }
            }
            foreach (config('solutions.pages', []) as $p) {
                if (($p['updated'] ?? '') === $today) {
                    $urls[] = url('/solutions/' . $p['slug']);
                }
            }
            if ($urls) {
                $urls[] = url('/blog');
                $urls[] = url('/sitemap.xml');
            }
        }
        if (!$urls) {
            $this->line('Nothing published or updated today.');
            return 0;
        }
        $host = parse_url(url('/'), PHP_URL_HOST);
        $res = Http::timeout(20)->post('https://api.indexnow.org/indexnow', [
            'host' => $host, 'key' => $key, 'keyLocation' => url('/' . $key . '.txt'), 'urlList' => array_values(array_unique($urls)),
        ]);
        $this->line(count($urls) . ' URL(s) submitted: HTTP ' . $res->status() . ' ' . ($res->status() === 200 || $res->status() === 202 ? 'ok' : $res->body()));
        return in_array($res->status(), [200, 202]) ? 0 : 1;
    }
}

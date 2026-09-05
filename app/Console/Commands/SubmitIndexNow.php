<?php

namespace App\Console\Commands;

use App\Support\Seo\IndexNow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 *   php artisan seo:indexnow --all            # every URL from the sitemaps (run after a deploy)
 *   php artisan seo:indexnow https://taketwostudio1603.com/proms
 */
class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow {urls?* : Absolute URLs to submit} {--all : Submit every URL listed in the pages + blog sitemaps}';

    protected $description = 'Notify Bing (IndexNow) that URLs changed so ChatGPT/Bing re-crawl them quickly';

    public function handle(): int
    {
        if (! IndexNow::enabled()) {
            $this->error('IndexNow is disabled: set INDEXNOW_KEY, make sure APP_URL is https and APP_ENV is not local.');

            return self::FAILURE;
        }

        $urls = (array) $this->argument('urls');

        if ($this->option('all')) {
            foreach (['/sitemap-pages.xml', '/sitemap-blog.xml'] as $sitemap) {
                $xml = Http::timeout(15)->get(url($sitemap))->body();
                preg_match_all('#<loc>(.*?)</loc>#', $xml, $m);
                $urls = array_merge($urls, $m[1]);
            }
        }

        $urls = array_values(array_unique($urls));

        if ($urls === []) {
            $this->warn('Nothing to submit.');

            return self::SUCCESS;
        }

        $ok = IndexNow::submit($urls);
        $this->{$ok ? 'info' : 'error'}(($ok ? 'Submitted ' : 'Failed to submit ').count($urls).' URL(s) to IndexNow.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}

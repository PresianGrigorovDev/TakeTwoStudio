<?php

namespace App\Support\Seo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pings IndexNow (Bing / Yandex / Seznam / Naver) whenever a public URL changes,
 * so Bing - and therefore ChatGPT search, which relies on Bing's index -
 * re-crawls within hours instead of weeks. Google ignores IndexNow.
 *
 * Enabled when INDEXNOW_KEY is set, APP_URL is https and the app is not local.
 * The key file is served at /{key}.txt (routes/web.php).
 */
final class IndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public static function key(): ?string
    {
        $key = trim((string) config('services.indexnow.key'));

        return $key !== '' ? $key : null;
    }

    public static function enabled(): bool
    {
        return self::key() !== null
            && str_starts_with((string) config('app.url'), 'https://')
            && ! app()->environment('local');
    }

    /** Submit right away. Returns true when IndexNow accepted the batch. */
    public static function submit(array $urls): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        $urls = collect($urls)
            ->filter(fn ($u) => is_string($u))
            ->map(fn (string $u) => preg_replace('#^http://'.preg_quote($host, '#').'#i', 'https://'.$host, $u))
            ->filter(fn (string $u) => str_starts_with($u, 'https://'.$host))
            ->unique()
            ->values()
            ->all();

        if ($urls === []) {
            return false;
        }

        try {
            $response = Http::timeout(5)->acceptJson()->post(self::ENDPOINT, [
                'host' => $host,
                'key' => self::key(),
                'keyLocation' => rtrim((string) config('app.url'), '/').'/'.self::key().'.txt',
                'urlList' => array_slice($urls, 0, 10000),
            ]);

            if (! $response->successful()) {
                Log::warning('IndexNow rejected submission', ['status' => $response->status(), 'body' => $response->body(), 'urls' => count($urls)]);
            }

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('IndexNow submission failed: '.$e->getMessage());

            return false;
        }
    }

    /** Submit after the HTTP response is sent, so admin saves never wait on Bing. */
    public static function submitLater(array $urls): void
    {
        if (! self::enabled()) {
            return;
        }

        app()->terminating(fn () => self::submit($urls));
    }
}

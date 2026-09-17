<?php

namespace App\Support;

/**
 * Cache busting for self-hosted assets on shared hosting: public/.htaccess
 * caches css/js for a month and there is no build step, so the file mtime
 * (which changes on every git checkout) is appended as ?v=.
 */
final class Assets
{
    public static function versioned(string $path): string
    {
        $file = public_path($path);

        // is_file() guard: a filemtime() warning becomes an ErrorException in tests.
        $version = is_file($file) ? (string) filemtime($file) : '0';

        return asset($path).'?v='.$version;
    }
}

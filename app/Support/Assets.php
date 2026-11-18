<?php

namespace App\Support;

/**
 * Cache busting for self-hosted assets on shared hosting: public/.htaccess
 * caches css/js for a year as immutable and there is no build step, so the
 * file mtime (which changes on every git checkout) is appended as ?v=.
 * Every local css/js URL must go through this helper.
 */
final class Assets
{
    public static function versioned(string $path, bool $absolute = false): string
    {
        $file = public_path($path);

        // is_file() guard: a filemtime() warning becomes an ErrorException in tests.
        $version = is_file($file) ? (string) filemtime($file) : '0';

        $url = $absolute ? asset($path) : '/'.ltrim($path, '/');

        return $url.'?v='.$version;
    }
}

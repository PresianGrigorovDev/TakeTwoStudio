<?php

namespace App\Support\Seo;

/**
 * The prom season we sell right now. Balls happen in late May / June; classes
 * book from September, so from July onwards the season is the NEXT year.
 * Override with PROM_SEASON_YEAR in .env if needed.
 */
final class PromSeason
{
    public static function year(): int
    {
        $configured = (int) config('seo.prom_season_year');

        if ($configured >= 2024) {
            return $configured;
        }

        return now()->month >= 7 ? now()->year + 1 : now()->year;
    }
}

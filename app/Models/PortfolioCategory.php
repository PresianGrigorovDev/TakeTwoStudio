<?php

namespace App\Models;

use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Model;

class PortfolioCategory extends Model
{
    protected $guarded = [];

    /**
     * Covers fill a 3:2 card (object-fit: cover) that is ~390 css px wide on phones
     * (~1020 device px at DPR 2.6) and ~650 px on desktop: fit inside this box, no crop.
     */
    public const COVER_MAX = 1200;

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('nav.portfolio_categories'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('nav.portfolio_categories'));

        // Safety net for covers that skip the Filament client-side resize.
        static::created(fn (PortfolioCategory $category) => $category->optimizeCover());
        static::updated(function (PortfolioCategory $category) {
            if ($category->wasChanged('cover_image')) {
                $category->optimizeCover();
            }
        });
    }

    /** Visible categories for the nav/footer (rendered on every page) - cached for an hour. */
    public static function visibleCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('nav.portfolio_categories', 3600, fn () => static::where('is_visible', true)->orderBy('display_order')->get());
    }

    private function optimizeCover(): void
    {
        if (! empty($this->cover_image)) {
            ImageOptimizer::optimize('public', $this->cover_image, self::COVER_MAX, 82, self::COVER_MAX);
        }
    }
}

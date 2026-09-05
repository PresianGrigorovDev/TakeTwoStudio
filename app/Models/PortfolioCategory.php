<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioCategory extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('nav.portfolio_categories'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('nav.portfolio_categories'));
    }

    /** Visible categories for the nav/footer (rendered on every page) - cached for an hour. */
    public static function visibleCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('nav.portfolio_categories', 3600, fn () => static::where('is_visible', true)->orderBy('display_order')->get());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Frequently asked question shown (and emitted as FAQPage schema) on the page
 * identified by page_slug: weddings, proms, baptism, commercial, family,
 * portrait, automotive, architectural, events or general.
 */
class Faq extends Model
{
    protected $fillable = ['page_slug', 'question', 'answer', 'sort_order', 'is_visible'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        $ping = function (Faq $faq) {
            if ($faq->page_slug && \App\Support\Seo\ServiceCatalog::has($faq->page_slug)) {
                \App\Support\Seo\IndexNow::submitLater([url($faq->page_slug)]);
            }
        };

        static::saved($ping);
        static::deleted($ping);
    }

    public const PAGES = [
        'general' => 'Общи',
        'weddings' => 'Сватби',
        'proms' => 'Абитуриентски балове',
        'baptism' => 'Кръщенета',
        'commercial' => 'Рекламна фотография',
        'family' => 'Семейни фотосесии',
        'portrait' => 'Портрети',
        'automotive' => 'Автомобилна фотография',
        'architectural' => 'Архитектурна фотография',
        'events' => 'Събития',
    ];

    public function scopeForPage(Builder $query, string $slug): Builder
    {
        return $query->where('page_slug', $slug);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Visible, ordered FAQs for a page. */
    public static function forPageVisible(string $slug)
    {
        return static::forPage($slug)->visible()->ordered()->get();
    }
}

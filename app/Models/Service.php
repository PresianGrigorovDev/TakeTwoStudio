<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;
use App\Support\ImageOptimizer;

class Service extends Model
{
    use LogsActivity;
    protected $guarded = [];

    /** Hero = full-width cover background behind a dark overlay (LCP of every service page): fit inside this box (px). */
    public const HERO_MAX = 1920;

    /** WebP sibling quality for heroes: q65-72 is indistinguishable behind the 40-50% overlay (tested 17.10.2026). */
    public const HERO_WEBP_QUALITY = 70;

    protected $casts = [
        'is_active' => 'boolean',
        'video_uploaded_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (Service $service) {
            if ($service->slug && \App\Support\Seo\ServiceCatalog::has($service->slug)) {
                \App\Support\Seo\IndexNow::submitLater([url($service->slug), url('/')]);
            }

            if (($service->wasRecentlyCreated || $service->wasChanged('hero_image')) && ! empty($service->hero_image)) { // wasChanged() is false on create
                ImageOptimizer::optimize('public', $service->hero_image, self::HERO_MAX, 82, self::HERO_MAX, self::HERO_WEBP_QUALITY);
            }
        });
    }

    public function packages()
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function extras()
    {
        return $this->hasMany(ServiceExtra::class);
    }

    public function promotions()
    {
        return $this->hasMany(ServicePromotion::class);
    }

    public function activePromotion()
    {
        return $this->hasOne(ServicePromotion::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }
}

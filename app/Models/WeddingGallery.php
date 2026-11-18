<?php

namespace App\Models;

use App\Support\Images;
use App\Support\Seo\IndexNow;
use Illuminate\Database\Eloquent\Model;

class WeddingGallery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (WeddingGallery $gallery) {
            $urls = [url('/weddings')];
            if ($gallery->is_active && $gallery->slug) {
                $urls[] = $gallery->url;
            }
            IndexNow::submitLater($urls);
        });
    }

    public function photos()
    {
        return $this->hasMany(WeddingGalleryPhoto::class)->orderBy('sort_order');
    }

    /** Public case-study page, or null when the gallery has no slug yet. */
    public function getUrlAttribute(): ?string
    {
        return $this->slug ? route('weddings.story', $this->slug) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return Images::url($this->cover_image);
    }

    /** "Евксиноград, Варна" / "Варна" / null */
    public function getPlaceLabelAttribute(): ?string
    {
        return implode(', ', array_filter([$this->venue, $this->location])) ?: null;
    }
}

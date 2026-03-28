<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeddingGallery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function photos()
    {
        return $this->hasMany(WeddingGalleryPhoto::class)->orderBy('sort_order');
    }
}

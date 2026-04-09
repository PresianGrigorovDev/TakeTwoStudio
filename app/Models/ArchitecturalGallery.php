<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchitecturalGallery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function photos()
    {
        return $this->hasMany(ArchitecturalGalleryPhoto::class)->orderBy('sort_order');
    }
}

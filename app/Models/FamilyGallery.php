<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyGallery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function photos()
    {
        return $this->hasMany(FamilyGalleryPhoto::class)->orderBy('sort_order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;



class BaptismGallery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function photos()
    {
        return $this->hasMany(BaptismGalleryPhoto::class)->orderBy('sort_order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeddingGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(WeddingGallery::class, 'wedding_gallery_id');
    }
}

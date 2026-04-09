<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortraitGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(PortraitGallery::class, 'portrait_gallery_id');
    }
}

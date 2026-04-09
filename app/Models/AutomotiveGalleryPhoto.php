<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomotiveGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(AutomotiveGallery::class, 'automotive_gallery_id');
    }
}

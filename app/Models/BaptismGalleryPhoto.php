<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaptismGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(BaptismGallery::class, 'baptism_gallery_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchitecturalGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(ArchitecturalGallery::class, 'architectural_gallery_id');
    }
}

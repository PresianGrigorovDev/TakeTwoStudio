<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(FamilyGallery::class, 'family_gallery_id');
    }
}

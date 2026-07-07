<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventGalleryPhoto extends Model
{
    protected $guarded = [];

    public function gallery()
    {
        return $this->belongsTo(EventGallery::class, 'event_gallery_id');
    }
}

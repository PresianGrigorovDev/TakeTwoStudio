<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeddingFaq extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}

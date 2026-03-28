<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromPortfolioPhoto extends Model
{
    protected $fillable = [
        'image_path',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}

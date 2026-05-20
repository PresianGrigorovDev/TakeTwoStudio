<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPortfolioPhoto extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];
}

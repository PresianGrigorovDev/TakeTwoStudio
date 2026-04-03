<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialPortfolioPhoto extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}

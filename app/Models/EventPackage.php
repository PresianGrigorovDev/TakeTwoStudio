<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPackage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features'    => 'array',
        'shoot_plan'  => 'array',
        'is_featured' => 'boolean',
        'is_visible'  => 'boolean',
        'price'       => 'decimal:2',
        'price_eur'   => 'decimal:2',
    ];
}

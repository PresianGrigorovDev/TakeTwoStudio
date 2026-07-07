<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaptismFaq extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}

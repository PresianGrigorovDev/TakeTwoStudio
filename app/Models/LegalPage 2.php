<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = [
        'slug',
        'title_bg',
        'content_bg',
        'effective_date',
        'is_published',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_published' => 'boolean',
    ];
}

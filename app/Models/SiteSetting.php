<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Settings::forget());
        static::deleted(fn () => Settings::forget());
    }
}

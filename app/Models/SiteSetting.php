<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        $forget = fn (SiteSetting $setting) => $setting->getConnection()->afterCommit(Settings::forget(...));
        static::saved($forget);
        static::deleted($forget);
    }
}

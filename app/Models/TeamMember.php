<?php

namespace App\Models;

use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $guarded = [];

    /** Team photos are shown as 140-180 px circles: fit uploads inside this square box (px). */
    public const IMAGE_MAX = 600;

    protected static function booted(): void
    {
        // Safety net for photos that skip the Filament client-side resize (other forms, imports, SSH).
        static::created(fn (TeamMember $member) => $member->optimizeImage());
        static::updated(function (TeamMember $member) {
            if ($member->wasChanged('image_path')) {
                $member->optimizeImage();
            }
        });
    }

    private function optimizeImage(): void
    {
        if (! empty($this->image_path)) {
            ImageOptimizer::optimize('public', $this->image_path, self::IMAGE_MAX, 82, self::IMAGE_MAX);
        }
    }
}

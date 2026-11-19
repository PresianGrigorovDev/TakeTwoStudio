<?php

namespace App\Models;

use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TeamMember extends Model
{
    protected $guarded = [];

    /** Team photos are shown as 140-180 px circles: fit uploads inside this square box (px). */
    public const IMAGE_MAX = 600;

    protected static function booted(): void
    {
        static::created(fn (TeamMember $member) => $member->syncFinanceAccount());
        static::updated(function (TeamMember $member) {
            if ($member->wasChanged('name')) {
                $member->syncFinanceAccount();
            }
        });

        // Safety net for photos that skip the Filament client-side resize (other forms, imports, SSH).
        static::created(fn (TeamMember $member) => $member->optimizeImage());
        static::updated(function (TeamMember $member) {
            if ($member->wasChanged('image_path')) {
                $member->optimizeImage();
            }
        });
    }

    private function syncFinanceAccount(): void
    {
        if (Schema::hasTable('finance_accounts')) {
            FinanceAccount::updateOrCreate(['key' => 'member:'.$this->id], [
                'name' => $this->name,
                'kind' => 'member',
                'team_member_id' => $this->id,
            ]);
        }
    }

    /** Calendar entries (engagements, busy time) this person is assigned to. */
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_team_member')
            ->using(TeamAssignment::class)
            ->withTimestamps();
    }

    /** Colour of this person in the team calendar (team_members.calendar_color). */
    public function getCalendarColorOrDefaultAttribute(): string
    {
        return $this->calendar_color ?: '#64748b';
    }

    private function optimizeImage(): void
    {
        if (! empty($this->image_path)) {
            ImageOptimizer::optimize('public', $this->image_path, self::IMAGE_MAX, 82, self::IMAGE_MAX);
        }
    }
}

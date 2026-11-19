<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAccount extends Model
{
    protected $guarded = [];

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function scopeActiveMembers(Builder $query): Builder
    {
        return $query->where('kind', 'member')->whereHas('teamMember', fn (Builder $member) => $member->where('is_active', true));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->teamMember?->name ?? $this->name;
    }

    public static function choices(bool $membersOnly = false): array
    {
        $query = self::query()->with('teamMember')->orderBy('id');
        if ($membersOnly) {
            $query->where('kind', 'member');
        }

        return $query->get()->mapWithKeys(fn (self $account) => [$account->id => $account->display_name])->all();
    }
}

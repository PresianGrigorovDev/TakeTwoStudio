<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAccount extends Model
{
    public const COMPANY = 'bank';

    public const LEGACY_CASH = 'cash';

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
        if ($this->key === self::LEGACY_CASH) {
            return 'Фирмена сметка';
        }

        return $this->teamMember?->name ?? $this->name;
    }

    public static function companyAccounts(): array
    {
        return once(fn () => self::whereIn('key', [self::COMPANY, self::LEGACY_CASH])->pluck('id', 'key')->map(fn ($id) => (int) $id)->all());
    }

    public static function companyId(): int
    {
        return self::companyAccounts()[self::COMPANY];
    }

    public static function canonicalId(?int $id): ?int
    {
        $accounts = self::companyAccounts();

        return $id !== null && $id === ($accounts[self::LEGACY_CASH] ?? null) ? $accounts[self::COMPANY] : $id;
    }

    public static function choices(bool $membersOnly = false, bool $includeLegacy = false): array
    {
        $query = self::query()->with('teamMember')->orderBy('id');
        if ($membersOnly) {
            $query->where('kind', 'member');
        } elseif (! $includeLegacy) {
            $query->where('key', '!=', self::LEGACY_CASH);
        }

        return $query->get()->mapWithKeys(fn (self $account) => [$account->id => $account->display_name])->all();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceMovement extends Model
{
    public const INCOME = 'income';
    public const EXPENSE = 'expense';
    public const REFUND = 'refund';
    public const TRANSFER = 'transfer';
    public const SETTLEMENT = 'settlement';

    public const KINDS = [
        self::INCOME => 'Плащане от клиент',
        self::EXPENSE => 'Разход',
        self::REFUND => 'Сума, върната на клиент',
        self::TRANSFER => 'Вътрешен превод',
        self::SETTLEMENT => 'Общо изравняване',
    ];

    public const STAGES = [
        'deposit' => 'Капаро',
        'installment' => 'Частична вноска',
        'final' => 'Окончателно плащане',
        'full' => 'Цялата сума',
    ];

    public const METHODS = [
        'cash' => 'В брой',
        'bank_transfer' => 'По банков път',
        'card' => 'С карта',
    ];

    protected $guarded = [];

    protected $casts = [
        'occurred_on' => 'date',
        'amount_cents' => 'integer',
        'details' => 'array',
        'voided_at' => 'datetime',
    ];

    protected function occurredOn(): Attribute
    {
        return Attribute::set(fn ($value) => \Illuminate\Support\Carbon::parse($value)->toDateString());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(FinanceProject::class, 'finance_project_id');
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'handled_by_id');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }
}

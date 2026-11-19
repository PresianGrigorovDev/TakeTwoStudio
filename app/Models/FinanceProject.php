<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class FinanceProject extends Model
{
    public const STUDIO = 'studio';

    public const UTILITY = 'utility';

    protected $guarded = [];

    protected $casts = [
        'agreed_cents' => 'integer',
        'shares' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinanceMovement::class);
    }

    public function utilityExpense(): HasOne
    {
        return $this->hasOne(FinanceMovement::class)->where('kind', FinanceMovement::EXPENSE);
    }

    public function isStudio(): bool
    {
        return $this->kind === self::STUDIO;
    }

    public function isUtility(): bool
    {
        return $this->kind === self::UTILITY;
    }

    public function isSharedExpense(): bool
    {
        return $this->isStudio() || $this->isUtility();
    }

    public static function installed(): bool
    {
        return once(fn () => Schema::hasTable('finance_movements'));
    }

    public static function studio(): self
    {
        return self::where('kind', self::STUDIO)->firstOrFail();
    }

    public static function forOrder(Order $order): ?self
    {
        return self::where('order_id', $order->id)
            ->orWhereHas('booking', fn (Builder $booking) => $booking->where('order_id', $order->id))
            ->orderByDesc('order_id')->first();
    }

    public static function forBooking(Booking $booking): ?self
    {
        return self::where('booking_id', $booking->id)->first()
            ?? ($booking->order ? self::forOrder($booking->order) : null);
    }
}

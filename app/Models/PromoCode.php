<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'source',
        'valid_from',
        'valid_until',
        'max_uses',
        'uses_count',
        'is_active',
    ];

    protected $casts = [
        'valid_from'     => 'date',
        'valid_until'    => 'date',
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    /**
     * Check if the promo code is currently valid.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->valid_from && Carbon::parse($this->valid_from)->greaterThan($today)) {
            return false;
        }

        if ($this->valid_until && Carbon::parse($this->valid_until)->lessThan($today)) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate how much to discount from a given price (in EUR).
     */
    public function calculateDiscount(float $price): float
    {
        if ($this->discount_type === 'percent') {
            return round($price * ($this->discount_value / 100), 2);
        }

        // fixed_eur – can't exceed the total price
        return min($this->discount_value, $price);
    }

    /**
     * Orders placed using this promo code.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Promotions (popup banners) that reference this code.
     */
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Human-readable discount label, e.g. "10%" or "€50".
     */
    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === 'percent') {
            return $this->discount_value . '%';
        }

        return '€' . number_format((float) $this->discount_value, 0);
    }
}

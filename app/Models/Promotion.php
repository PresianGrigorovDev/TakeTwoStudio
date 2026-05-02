<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_path',
        'redirect_url',
        'expires_at',
        'is_active',
        'popup_days_interval',
        'promo_code_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    /**
     * The promo code associated with this promotion (optional).
     */
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * Scope to get the single active promotion.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->latest()->limit(1);
    }
}

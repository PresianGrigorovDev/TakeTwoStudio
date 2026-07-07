<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Order $order) {
            $order->bookings()->delete();
        });
    }

    protected $fillable = [
        'name',
        'phone',
        'email',
        'service_type',
        'event_date',
        'start_time',
        'end_time',
        'price',
        'details',
        'status',
        'promo_code_id',
        'promo_code',
        'discount_amount',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function teamMembers()
    {
        return $this->belongsToMany(TeamMember::class, 'order_team_member');
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}

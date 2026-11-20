<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Order $order) {
            $order->bookings()->eachById(fn (Booking $booking) => $booking->delete());
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
        return $this->belongsToMany(TeamMember::class, 'order_team_member')
            ->using(TeamAssignment::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class)->where('is_primary_for_order', true);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function shoots(): HasMany
    {
        return $this->hasMany(OrderShoot::class);
    }
}

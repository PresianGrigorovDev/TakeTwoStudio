<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function teamMembers()
    {
        return $this->belongsToMany(TeamMember::class, 'order_team_member');
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

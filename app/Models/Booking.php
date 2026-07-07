<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'event_date',
        'start_time',
        'end_time',
        'service_type',
        'message',
        'status',
        'admin_notes',
        'order_id',
        'team_member_id',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function teamMember()
    {
        return $this->belongsTo(\App\Models\TeamMember::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('event_date', $date);
    }
}

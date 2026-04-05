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
        'team_member_id',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }
}

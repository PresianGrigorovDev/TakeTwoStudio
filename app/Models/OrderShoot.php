<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShoot extends Model
{
    public const SESSION = 'session';

    public const EVENT = 'event';

    public const KINDS = [
        self::SESSION => 'Фотосесия',
        self::EVENT => 'Събитие',
    ];

    protected $fillable = ['name', 'kind', 'booking_id', 'shot_on', 'shot_by_id'];

    protected $casts = ['shot_on' => 'date'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function shotBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shot_by_id');
    }

    public function scopeRemaining(Builder $query): Builder
    {
        return $query->whereNull('shot_on');
    }

    public function canSchedule(): bool
    {
        return $this->shot_on === null && ! $this->booking?->isActive();
    }

    public function bookingKind(): string
    {
        return $this->kind === self::SESSION ? Booking::KIND_SESSION : Booking::KIND_CLIENT;
    }
}

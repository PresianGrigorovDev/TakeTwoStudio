<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One entry of the internal team calendar: a client engagement (also the website
 * booking requests), a person's "busy" time (vacation, sick leave...) or the whole
 * studio closed. It may span several days (event_date..end_date, same hours each
 * day) and have several team members (booking_team_member).
 */
class Booking extends Model
{
    use HasFactory;

    public const KIND_CLIENT = 'client';

    public const KIND_BUSY = 'busy';

    public const KIND_CLOSED = 'closed';

    public const KINDS = [
        self::KIND_CLIENT => 'Ангажимент',
        self::KIND_BUSY => 'Лично зает (отпуск, болничен…)',
        self::KIND_CLOSED => 'Студиото е затворено',
    ];

    /** Statuses that occupy people in the calendar. */
    public const ACTIVE_STATUSES = ['pending', 'confirmed'];

    protected $fillable = [
        'kind',
        'name',
        'phone',
        'email',
        'event_date',
        'end_date',
        'start_time',
        'end_time',
        'service_type',
        'message',
        'status',
        'admin_notes',
        'order_id',
    ];

    protected $attributes = [
        'kind' => self::KIND_CLIENT,
    ];

    protected $casts = [
        'event_date' => 'date',
        'end_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function teamMembers()
    {
        return $this->belongsToMany(TeamMember::class, 'booking_team_member')->withTimestamps();
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

    /** Entries that occupy people (pending requests included, so nobody is double-booked). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /** Entries touching at least one day of [$from, $to]. */
    public function scopeOverlappingDays(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('event_date', '<=', $to)
            ->where(fn (Builder $q) => $q->whereDate('end_date', '>=', $from)
                ->orWhere(fn (Builder $q) => $q->whereNull('end_date')->whereDate('event_date', '>=', $from)));
    }

    /** Last day of the entry (end_date for multi-day entries). */
    public function getLastDateAttribute(): Carbon
    {
        return ($this->end_date && $this->end_date->gt($this->event_date)) ? $this->end_date : $this->event_date;
    }

    public function coversDate(string $date): bool
    {
        return $this->event_date->toDateString() <= $date && $this->last_date->toDateString() >= $date;
    }

    /** Hours "HH:00" this entry occupies on each of its days. */
    public function occupiedHours(): array
    {
        $hours = [];
        for ($h = (int) substr((string) $this->start_time, 0, 2); $h < (int) substr((string) $this->end_time, 0, 2); $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        return $hours;
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}

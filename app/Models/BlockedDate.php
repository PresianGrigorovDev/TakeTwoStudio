<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'blocked_hours',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'blocked_hours' => 'array',
    ];

    /**
     * Check if a specific hour is blocked on this date.
     * If blocked_hours is null, the entire day is blocked.
     */
    public function isFullDayBlocked(): bool
    {
        return empty($this->blocked_hours);
    }

    public function isHourBlocked(string $hour): bool
    {
        if ($this->isFullDayBlocked()) {
            return true;
        }

        return in_array($hour, $this->blocked_hours);
    }
}

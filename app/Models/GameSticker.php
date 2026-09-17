<?php

namespace App\Models;

use App\Http\Controllers\GameController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A physical QR sticker of the /igra game: a (target, loc) pair created by the studio.
 * The public link and the QR code are derived from it; statistics come from
 * game_events rows with the same target + loc (there is no foreign key on purpose,
 * events are logged even for stickers that were never registered here).
 */
class GameSticker extends Model
{
    protected $fillable = ['name', 'target', 'loc', 'notes', 'placed_at'];

    protected $casts = [
        'placed_at' => 'date',
    ];

    /** Public address encoded in the QR code. */
    public function getUrlAttribute(): string
    {
        return GameController::url($this->target, $this->loc);
    }

    /** Adds scans / wins / losses / vouchers / shares counters (sortable subselects). */
    public function scopeWithEventCounts(Builder $query): Builder
    {
        $query->select('game_stickers.*');

        foreach (['scans' => 'scan', 'wins' => 'win', 'losses' => 'lose', 'vouchers' => 'voucher', 'shares' => 'share'] as $alias => $event) {
            $query->selectSub(
                GameEvent::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('game_events.target', 'game_stickers.target')
                    ->whereColumn('game_events.loc', 'game_stickers.loc')
                    ->where('game_events.event', $event),
                $alias
            );
        }

        return $query;
    }
}

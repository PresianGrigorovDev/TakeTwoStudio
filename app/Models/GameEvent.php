<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One anonymous event from the QR sticker mini-game (/igra): scan, win, lose,
 * voucher (code generated in the browser) or share. Rows are append-only; the
 * only later write is redeemed_at, set from the admin when a code is honoured.
 */
class GameEvent extends Model
{
    public const UPDATED_AT = null;

    public const TARGETS = ['prom', 'wedding'];

    public const EVENTS = ['scan', 'win', 'lose', 'voucher', 'share', 'use'];

    public const LOC_PATTERN = '/^[a-z0-9-]{1,40}$/';

    /** 32-symbol alphabet without 0/O/1/I, mirrored in public/js/igra.js. */
    public const CODE_PATTERN = '/^VN-(PROM|WED)-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/';

    protected $fillable = [
        'target',
        'loc',
        'event',
        'code',
        'meta',
        'redeemed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    /** Voucher prefix segment for a target: VN-PROM-… for proms, VN-WED-… for weddings. */
    public static function prefixFor(string $target): string
    {
        return $target === 'wedding' ? 'WED' : 'PROM';
    }
}

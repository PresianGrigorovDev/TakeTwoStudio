<?php

namespace App\Support;

use App\Models\GameEvent;

/**
 * Voucher codes of the /igra game (VN-PROM-XXXX / VN-WED-XXXX).
 *
 * The code is generated in the player's browser and reaches the server as a
 * game_events row (event = voucher). That row IS the voucher: it is valid for
 * VALIDITY_HOURS after it was created, until it is redeemed (redeemed_at) either
 * by an order in the site calculators or manually by the studio in Filament.
 *
 * Two discount levels, both site settings per game (Маркетинг → QR Игра – статистика):
 *  - base:   what a win earns (default 5%)
 *  - shared: what the player gets after sharing the Story artefact (default 15%)
 * Both are frozen into the voucher row at issue time (meta.percent / meta.percent_shared),
 * so later changes never alter what a player was promised; a "share" event boosts
 * meta.percent to the shared level.
 */
final class GameVoucher
{
    public const DEFAULT_BASE_PERCENT = 5;

    public const DEFAULT_SHARED_PERCENT = 15;

    public const MAX_PERCENT = 90;

    public const VALIDITY_HOURS = 72;

    /** Share methods that count as "the player shared the Story" and unlock the shared percent. */
    public const BOOSTING_METHODS = ['share', 'download', 'confirm'];

    /** @var array<string,array{base:string,shared:string}> */
    public const SETTING_KEYS = [
        'prom' => ['base' => 'game_discount_base_prom', 'shared' => 'game_discount_shared_prom'],
        'wedding' => ['base' => 'game_discount_base_wedding', 'shared' => 'game_discount_shared_wedding'],
    ];

    /** @param  'base'|'shared'  $level */
    public static function percentFor(string $target, string $level = 'base'): int
    {
        $keys = self::SETTING_KEYS[$target] ?? self::SETTING_KEYS['prom'];
        $default = $level === 'shared' ? self::DEFAULT_SHARED_PERCENT : self::DEFAULT_BASE_PERCENT;
        $raw = Settings::get($keys[$level] ?? $keys['base']);

        $percent = $raw === null ? $default : (int) $raw;

        return max(0, min(self::MAX_PERCENT, $percent));
    }

    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    public static function isGameCode(string $code): bool
    {
        return preg_match(GameEvent::CODE_PATTERN, self::normalize($code)) === 1;
    }

    /** The voucher row for this code regardless of state (latest), or null. */
    public static function row(string $code): ?GameEvent
    {
        $code = self::normalize($code);

        if (! self::isGameCode($code)) {
            return null;
        }

        return GameEvent::query()
            ->where('event', 'voucher')
            ->where('code', $code)
            ->latest('created_at')
            ->first();
    }

    /** The unredeemed, still valid voucher row for this code, or null. */
    public static function find(string $code): ?GameEvent
    {
        $voucher = self::row($code);

        if ($voucher === null || $voucher->redeemed_at !== null) {
            return null;
        }

        return $voucher->created_at >= now()->subHours(self::VALIDITY_HOURS) ? $voucher : null;
    }

    /** Percent the player currently holds (base, or shared once boosted). Falls back to the current setting. */
    public static function percentOf(GameEvent $voucher): int
    {
        $frozen = (int) ($voucher->meta['percent'] ?? 0);

        return $frozen > 0 ? min(self::MAX_PERCENT, $frozen) : self::percentFor($voucher->target);
    }

    /** Upgrade an unredeemed voucher to its shared percent (idempotent). Returns the resulting percent. */
    public static function boost(GameEvent $voucher): int
    {
        $meta = $voucher->meta ?? [];
        $shared = (int) ($meta['percent_shared'] ?? 0);
        if ($shared <= 0) {
            $shared = self::percentFor($voucher->target, 'shared');
        }

        if ($voucher->redeemed_at === null && $shared > (int) ($meta['percent'] ?? 0)) {
            $meta['percent'] = $shared;
            $meta['boosted_at'] = now()->toIso8601String();
            $voucher->forceFill(['meta' => $meta])->save();
        }

        return self::percentOf($voucher);
    }

    public static function redeem(GameEvent $voucher): void
    {
        $voucher->forceFill(['redeemed_at' => now()])->save();
    }

    /**
     * Discount to record for an order whose final_price was ALREADY reduced by the
     * calculator in the browser (applyPromoDiscount), so the server must not subtract
     * it a second time. For a percent discount the original price is p / (1 - d).
     */
    public static function discountFromDiscountedPrice(float $discountedPrice, string $type, float $value): float
    {
        if ($type === 'percent') {
            if ($value <= 0 || $value >= 100) {
                return 0.0;
            }

            return round($discountedPrice * $value / (100 - $value), 2);
        }

        return round(max(0, $value), 2);
    }
}

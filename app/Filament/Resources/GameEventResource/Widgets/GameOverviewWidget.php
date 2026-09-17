<?php

namespace App\Filament\Resources\GameEventResource\Widgets;

use App\Models\GameEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Headline numbers for the QR game: all-time totals plus the last 7 days trend,
 * and the voucher pipeline (active in the 72h window / redeemed by the studio).
 */
class GameOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $counts = GameEvent::query()
            ->selectRaw(
                "SUM(CASE WHEN event = 'scan' THEN 1 ELSE 0 END) AS scans, "
                ."SUM(CASE WHEN event = 'win' THEN 1 ELSE 0 END) AS wins, "
                ."SUM(CASE WHEN event = 'voucher' THEN 1 ELSE 0 END) AS vouchers, "
                ."SUM(CASE WHEN event = 'voucher' AND redeemed_at IS NOT NULL THEN 1 ELSE 0 END) AS redeemed"
            )
            ->first();

        $scans = (int) ($counts->scans ?? 0);
        $wins = (int) ($counts->wins ?? 0);
        $vouchers = (int) ($counts->vouchers ?? 0);
        $redeemed = (int) ($counts->redeemed ?? 0);

        $activeVouchers = GameEvent::query()
            ->where('event', 'voucher')
            ->whereNull('redeemed_at')
            ->where('created_at', '>=', now()->subHours(72))
            ->count();

        $scansLast7 = GameEvent::query()->where('event', 'scan')->where('created_at', '>=', now()->subDays(7))->count();
        $winsLast7 = GameEvent::query()->where('event', 'win')->where('created_at', '>=', now()->subDays(7))->count();

        $winRate = $scans > 0 ? (int) round($wins / $scans * 100) : null;

        return [
            Stat::make('Сканирания', number_format($scans))
                ->description($scansLast7.' през последните 7 дни')
                ->descriptionIcon('heroicon-m-qr-code')
                ->chart($this->dailySeries('scan'))
                ->color('gray'),

            Stat::make('Победи', number_format($wins))
                ->description($winRate === null ? 'Няма сканирания' : $winRate.'% от сканиранията · '.$winsLast7.' за 7 дни')
                ->descriptionIcon('heroicon-m-trophy')
                ->chart($this->dailySeries('win'))
                ->color('success'),

            Stat::make('Активни ваучери (72 ч)', number_format($activeVouchers))
                ->description($vouchers.' издадени общо')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('warning'),

            Stat::make('Използвани ваучери', number_format($redeemed))
                ->description($vouchers > 0 ? (int) round($redeemed / $vouchers * 100).'% от издадените' : 'Няма издадени')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),
        ];
    }

    /**
     * Daily counts for the last 14 days (oldest first) for the mini sparkline.
     *
     * @return array<int, int>
     */
    private function dailySeries(string $event): array
    {
        $since = now()->subDays(13)->startOfDay();

        $rows = GameEvent::query()
            ->where('event', $event)
            ->where('created_at', '>=', $since)
            ->get(['created_at'])
            ->groupBy(fn (GameEvent $e) => $e->created_at->toDateString())
            ->map->count();

        $series = [];
        for ($i = 0; $i < 14; $i++) {
            $series[] = (int) ($rows[$since->copy()->addDays($i)->toDateString()] ?? 0);
        }

        return $series;
    }
}

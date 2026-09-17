<?php

namespace App\Filament\Resources\GameEventResource\Widgets;

use App\Filament\Resources\GameEventResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/**
 * Funnel of the QR sticker game per target and sticker location:
 * scans -> wins / losses -> vouchers -> shares, with conversion percentages.
 * Shown on the "QR Игра – статистика" page (Маркетинг), not on the dashboard.
 */
class GameStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.game-stats';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public const TARGET_LABELS = [
        'prom' => 'Абитуриентски бал',
        'wedding' => 'Сватба',
    ];

    private const COUNTERS = ['scans', 'wins', 'losses', 'vouchers', 'shares'];

    /**
     * @return array<string, array{label:string, rows:array<int,array<string,mixed>>, totals:array<string,mixed>}>
     */
    public function getStats(): array
    {
        // One GROUP BY query; CASE WHEN (not FILTER / IF) so it runs on both MySQL and SQLite.
        $rows = DB::table('game_events')
            ->selectRaw(
                'target, loc, '
                ."SUM(CASE WHEN event = 'scan' THEN 1 ELSE 0 END) AS scans, "
                ."SUM(CASE WHEN event = 'win' THEN 1 ELSE 0 END) AS wins, "
                ."SUM(CASE WHEN event = 'lose' THEN 1 ELSE 0 END) AS losses, "
                ."SUM(CASE WHEN event = 'voucher' THEN 1 ELSE 0 END) AS vouchers, "
                ."SUM(CASE WHEN event = 'share' THEN 1 ELSE 0 END) AS shares"
            )
            ->groupBy('target', 'loc')
            ->orderByDesc('scans')
            ->orderBy('loc')
            ->get();

        $stats = [];

        foreach (self::TARGET_LABELS as $target => $label) {
            $targetRows = $rows->where('target', $target);

            if ($targetRows->isEmpty()) {
                continue;
            }

            $totals = array_fill_keys(self::COUNTERS, 0);
            $list = [];

            foreach ($targetRows as $row) {
                $counts = [];
                foreach (self::COUNTERS as $counter) {
                    $counts[$counter] = (int) $row->{$counter};
                    $totals[$counter] += $counts[$counter];
                }

                $list[] = $this->withRates($counts + ['loc' => $row->loc ?: '(без локация)']);
            }

            $stats[$target] = [
                'label' => $label,
                'rows' => $list,
                'totals' => $this->withRates($totals),
            ];
        }

        return $stats;
    }

    /** @param  array<string,mixed>  $row */
    private function withRates(array $row): array
    {
        $scans = (int) $row['scans'];

        $row['win_rate'] = $scans > 0 ? (int) round($row['wins'] / $scans * 100) : null;
        $row['voucher_rate'] = $scans > 0 ? (int) round($row['vouchers'] / $scans * 100) : null;

        return $row;
    }

    protected function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'resourceUrl' => GameEventResource::getUrl(),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GameEventResource;
use App\Filament\Resources\GameEventResource\Widgets\GameOverviewWidget;
use App\Filament\Resources\GameEventResource\Widgets\GameStatsWidget;
use App\Filament\Resources\GameStickerResource;
use App\Http\Controllers\GameController;
use App\Models\GameEvent;
use App\Models\GameSticker;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * "QR Игра – статистика": headline numbers, funnel per sticker location, and the list of
 * locations that were actually scanned (linked to their sticker, or offering to create one).
 * Links and QR codes themselves are managed in Маркетинг → QR стикери (GameStickerResource).
 */
class GameStats extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'QR Игра – статистика';

    protected static ?string $title = 'QR Игра – статистика';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'game-stats';

    protected static string $view = 'filament.pages.game-stats';

    /**
     * Sticker locations that have been scanned at least once, with their sticker (if registered).
     *
     * @return array<int,array{target:string,label:string,loc:string,scans:int,url:string,sticker:?GameSticker,createUrl:string}>
     */
    public function getKnownLocations(): array
    {
        $stickers = GameSticker::query()->get()->keyBy(fn (GameSticker $s) => $s->target.'|'.$s->loc);

        return GameEvent::query()
            ->selectRaw("target, loc, SUM(CASE WHEN event = 'scan' THEN 1 ELSE 0 END) AS scans")
            ->whereNotNull('loc')
            ->groupBy('target', 'loc')
            ->orderBy('target')
            ->orderByDesc('scans')
            ->orderBy('loc')
            ->get()
            ->map(fn ($row) => [
                'target' => $row->target,
                'label' => GameEventResource::TARGET_LABELS[$row->target] ?? $row->target,
                'loc' => $row->loc,
                'scans' => (int) $row->scans,
                'url' => GameController::url($row->target, $row->loc),
                'sticker' => $stickers->get($row->target.'|'.$row->loc),
                'createUrl' => GameStickerResource::getUrl('create', ['target' => $row->target, 'loc' => $row->loc]),
            ])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stickers')
                ->label('QR стикери')
                ->icon('heroicon-m-qr-code')
                ->url(GameStickerResource::getUrl()),

            Action::make('events')
                ->label('Всички събития и кодове')
                ->icon('heroicon-m-table-cells')
                ->color('gray')
                ->url(GameEventResource::getUrl()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GameOverviewWidget::class,
            GameStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GameEventResource;
use App\Filament\Resources\GameEventResource\Widgets\GameOverviewWidget;
use App\Filament\Resources\GameEventResource\Widgets\GameStatsWidget;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * "QR Игра – статистика": headline numbers + funnel per sticker location.
 * Lives in the Маркетинг navigation group next to the raw events list.
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('events')
                ->label('Всички събития и кодове')
                ->icon('heroicon-m-table-cells')
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

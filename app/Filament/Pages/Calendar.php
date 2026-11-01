<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TeamAvailabilityWidget;
use App\Filament\Widgets\TeamCalendarWidget;
use Filament\Pages\Page;

/** Работа → Календар: the team month calendar with "who is free" underneath. */
class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Работа';

    protected static ?string $navigationLabel = 'Календар';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Календар на екипа';

    protected static ?string $slug = 'kalendar';

    protected static string $view = 'filament.pages.calendar';

    protected function getHeaderWidgets(): array
    {
        return [TeamCalendarWidget::make(['origin' => 'page'])];
    }

    protected function getFooterWidgets(): array
    {
        return [TeamAvailabilityWidget::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}

<?php

namespace App\Filament\Resources\FinanceMovementResource\Pages;

use App\Filament\Resources\FinanceMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceMovements extends ListRecords
{
    protected static string $resource = FinanceMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Запиши движение')];
    }
}

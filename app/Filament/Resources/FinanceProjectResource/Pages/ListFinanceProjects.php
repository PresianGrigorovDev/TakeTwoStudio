<?php

namespace App\Filament\Resources\FinanceProjectResource\Pages;

use App\Filament\Resources\FinanceProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceProjects extends ListRecords
{
    protected static string $resource = FinanceProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Нов финансов проект')];
    }
}

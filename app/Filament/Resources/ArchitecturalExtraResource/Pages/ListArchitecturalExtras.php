<?php

namespace App\Filament\Resources\ArchitecturalExtraResource\Pages;

use App\Filament\Resources\ArchitecturalExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArchitecturalExtras extends ListRecords
{
    protected static string $resource = ArchitecturalExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

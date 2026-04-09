<?php

namespace App\Filament\Resources\FamilyExtraResource\Pages;

use App\Filament\Resources\FamilyExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFamilyExtras extends ListRecords
{
    protected static string $resource = FamilyExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

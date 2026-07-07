<?php

namespace App\Filament\Resources\FamilyPackageResource\Pages;

use App\Filament\Resources\FamilyPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFamilyPackages extends ListRecords
{
    protected static string $resource = FamilyPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

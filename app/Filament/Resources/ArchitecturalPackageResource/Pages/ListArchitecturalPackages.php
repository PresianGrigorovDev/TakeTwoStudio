<?php

namespace App\Filament\Resources\ArchitecturalPackageResource\Pages;

use App\Filament\Resources\ArchitecturalPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArchitecturalPackages extends ListRecords
{
    protected static string $resource = ArchitecturalPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

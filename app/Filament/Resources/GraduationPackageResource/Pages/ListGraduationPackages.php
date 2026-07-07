<?php

namespace App\Filament\Resources\GraduationPackageResource\Pages;

use App\Filament\Resources\GraduationPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGraduationPackages extends ListRecords
{
    protected static string $resource = GraduationPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

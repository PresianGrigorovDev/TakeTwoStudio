<?php

namespace App\Filament\Resources\AutomotivePackageResource\Pages;

use App\Filament\Resources\AutomotivePackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutomotivePackages extends ListRecords
{
    protected static string $resource = AutomotivePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

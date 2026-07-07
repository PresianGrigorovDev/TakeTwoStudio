<?php

namespace App\Filament\Resources\PortraitPackageResource\Pages;

use App\Filament\Resources\PortraitPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPortraitPackages extends ListRecords
{
    protected static string $resource = PortraitPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\EventPackageResource\Pages;

use App\Filament\Resources\EventPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventPackages extends ListRecords
{
    protected static string $resource = EventPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

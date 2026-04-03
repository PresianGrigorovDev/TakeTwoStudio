<?php

namespace App\Filament\Resources\CommercialPackageResource\Pages;

use App\Filament\Resources\CommercialPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommercialPackages extends ListRecords
{
    protected static string $resource = CommercialPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\BaptismPackageResource\Pages;

use App\Filament\Resources\BaptismPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaptismPackages extends ListRecords
{
    protected static string $resource = BaptismPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

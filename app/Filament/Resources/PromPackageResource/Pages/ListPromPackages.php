<?php

namespace App\Filament\Resources\PromPackageResource\Pages;

use App\Filament\Resources\PromPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromPackages extends ListRecords
{
    protected static string $resource = PromPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

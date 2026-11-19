<?php

namespace App\Filament\Resources\PortraitPackageResource\Pages;

use App\Filament\Resources\PortraitPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListPortraitPackages extends AuditedListRecords
{
    protected static string $resource = PortraitPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\AutomotivePackageResource\Pages;

use App\Filament\Resources\AutomotivePackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListAutomotivePackages extends AuditedListRecords
{
    protected static string $resource = AutomotivePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

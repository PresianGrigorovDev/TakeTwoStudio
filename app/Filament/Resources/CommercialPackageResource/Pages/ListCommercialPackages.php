<?php

namespace App\Filament\Resources\CommercialPackageResource\Pages;

use App\Filament\Resources\CommercialPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListCommercialPackages extends AuditedListRecords
{
    protected static string $resource = CommercialPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

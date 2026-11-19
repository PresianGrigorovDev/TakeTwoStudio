<?php

namespace App\Filament\Resources\FamilyPackageResource\Pages;

use App\Filament\Resources\FamilyPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListFamilyPackages extends AuditedListRecords
{
    protected static string $resource = FamilyPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

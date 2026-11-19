<?php

namespace App\Filament\Resources\ArchitecturalPackageResource\Pages;

use App\Filament\Resources\ArchitecturalPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListArchitecturalPackages extends AuditedListRecords
{
    protected static string $resource = ArchitecturalPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

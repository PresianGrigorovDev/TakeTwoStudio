<?php

namespace App\Filament\Resources\GraduationPackageResource\Pages;

use App\Filament\Resources\GraduationPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListGraduationPackages extends AuditedListRecords
{
    protected static string $resource = GraduationPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\EventPackageResource\Pages;

use App\Filament\Resources\EventPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListEventPackages extends AuditedListRecords
{
    protected static string $resource = EventPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

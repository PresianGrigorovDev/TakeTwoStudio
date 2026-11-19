<?php

namespace App\Filament\Resources\PromPackageResource\Pages;

use App\Filament\Resources\PromPackageResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListPromPackages extends AuditedListRecords
{
    protected static string $resource = PromPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

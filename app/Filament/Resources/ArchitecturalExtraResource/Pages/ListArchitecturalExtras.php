<?php

namespace App\Filament\Resources\ArchitecturalExtraResource\Pages;

use App\Filament\Resources\ArchitecturalExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListArchitecturalExtras extends AuditedListRecords
{
    protected static string $resource = ArchitecturalExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

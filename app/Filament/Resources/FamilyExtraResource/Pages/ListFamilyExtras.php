<?php

namespace App\Filament\Resources\FamilyExtraResource\Pages;

use App\Filament\Resources\FamilyExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListFamilyExtras extends AuditedListRecords
{
    protected static string $resource = FamilyExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

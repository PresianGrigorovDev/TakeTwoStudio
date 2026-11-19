<?php

namespace App\Filament\Resources\CommercialExtraResource\Pages;

use App\Filament\Resources\CommercialExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListCommercialExtras extends AuditedListRecords
{
    protected static string $resource = CommercialExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

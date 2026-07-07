<?php

namespace App\Filament\Resources\CommercialExtraResource\Pages;

use App\Filament\Resources\CommercialExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommercialExtras extends ListRecords
{
    protected static string $resource = CommercialExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

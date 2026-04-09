<?php

namespace App\Filament\Resources\CommercialExtraResource\Pages;

use App\Filament\Resources\CommercialExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommercialExtra extends EditRecord
{
    protected static string $resource = CommercialExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

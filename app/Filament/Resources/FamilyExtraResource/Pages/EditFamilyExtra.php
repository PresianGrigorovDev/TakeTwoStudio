<?php

namespace App\Filament\Resources\FamilyExtraResource\Pages;

use App\Filament\Resources\FamilyExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamilyExtra extends EditRecord
{
    protected static string $resource = FamilyExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

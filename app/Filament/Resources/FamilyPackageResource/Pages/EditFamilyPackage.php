<?php

namespace App\Filament\Resources\FamilyPackageResource\Pages;

use App\Filament\Resources\FamilyPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamilyPackage extends EditRecord
{
    protected static string $resource = FamilyPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

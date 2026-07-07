<?php

namespace App\Filament\Resources\CommercialPackageResource\Pages;

use App\Filament\Resources\CommercialPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommercialPackage extends EditRecord
{
    protected static string $resource = CommercialPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

<?php

namespace App\Filament\Resources\PortraitPackageResource\Pages;

use App\Filament\Resources\PortraitPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPortraitPackage extends EditRecord
{
    protected static string $resource = PortraitPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

<?php

namespace App\Filament\Resources\AutomotivePackageResource\Pages;

use App\Filament\Resources\AutomotivePackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomotivePackage extends EditRecord
{
    protected static string $resource = AutomotivePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

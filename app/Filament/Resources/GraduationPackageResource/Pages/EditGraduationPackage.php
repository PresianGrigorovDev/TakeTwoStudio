<?php

namespace App\Filament\Resources\GraduationPackageResource\Pages;

use App\Filament\Resources\GraduationPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGraduationPackage extends EditRecord
{
    protected static string $resource = GraduationPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

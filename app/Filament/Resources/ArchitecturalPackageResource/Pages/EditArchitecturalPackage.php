<?php

namespace App\Filament\Resources\ArchitecturalPackageResource\Pages;

use App\Filament\Resources\ArchitecturalPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArchitecturalPackage extends EditRecord
{
    protected static string $resource = ArchitecturalPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

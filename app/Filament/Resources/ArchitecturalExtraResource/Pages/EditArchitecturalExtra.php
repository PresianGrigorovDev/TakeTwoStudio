<?php

namespace App\Filament\Resources\ArchitecturalExtraResource\Pages;

use App\Filament\Resources\ArchitecturalExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArchitecturalExtra extends EditRecord
{
    protected static string $resource = ArchitecturalExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

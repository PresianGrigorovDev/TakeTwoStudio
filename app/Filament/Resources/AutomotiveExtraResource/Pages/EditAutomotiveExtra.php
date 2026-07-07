<?php

namespace App\Filament\Resources\AutomotiveExtraResource\Pages;

use App\Filament\Resources\AutomotiveExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomotiveExtra extends EditRecord
{
    protected static string $resource = AutomotiveExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

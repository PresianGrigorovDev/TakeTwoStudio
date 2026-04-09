<?php

namespace App\Filament\Resources\PortraitExtraResource\Pages;

use App\Filament\Resources\PortraitExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPortraitExtra extends EditRecord
{
    protected static string $resource = PortraitExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

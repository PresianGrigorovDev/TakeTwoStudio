<?php

namespace App\Filament\Resources\EventExtraResource\Pages;

use App\Filament\Resources\EventExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventExtra extends EditRecord
{
    protected static string $resource = EventExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

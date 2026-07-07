<?php

namespace App\Filament\Resources\PromExtraResource\Pages;

use App\Filament\Resources\PromExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromExtra extends EditRecord
{
    protected static string $resource = PromExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

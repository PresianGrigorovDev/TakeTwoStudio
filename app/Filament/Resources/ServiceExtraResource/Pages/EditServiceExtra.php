<?php

namespace App\Filament\Resources\ServiceExtraResource\Pages;

use App\Filament\Resources\ServiceExtraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceExtra extends EditRecord
{
    protected static string $resource = ServiceExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

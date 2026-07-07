<?php

namespace App\Filament\Resources\WeddingExtraResource\Pages;

use App\Filament\Resources\WeddingExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWeddingExtra extends EditRecord
{
    protected static string $resource = WeddingExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

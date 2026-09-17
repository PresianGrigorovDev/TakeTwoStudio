<?php

namespace App\Filament\Resources\GameStickerResource\Pages;

use App\Filament\Resources\GameStickerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameSticker extends EditRecord
{
    protected static string $resource = GameStickerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Изтрий'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Resources\GameStickerResource\Pages;

use App\Filament\Resources\GameStickerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameStickers extends ListRecords
{
    protected static string $resource = GameStickerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Нов стикер'),
        ];
    }
}

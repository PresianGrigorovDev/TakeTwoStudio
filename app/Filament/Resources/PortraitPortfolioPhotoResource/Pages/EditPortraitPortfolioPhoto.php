<?php

namespace App\Filament\Resources\PortraitPortfolioPhotoResource\Pages;

use App\Filament\Resources\PortraitPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortraitPortfolioPhoto extends EditRecord
{
    protected static string $resource = PortraitPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

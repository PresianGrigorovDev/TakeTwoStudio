<?php

namespace App\Filament\Resources\CommercialPortfolioPhotoResource\Pages;

use App\Filament\Resources\CommercialPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommercialPortfolioPhoto extends EditRecord
{
    protected static string $resource = CommercialPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

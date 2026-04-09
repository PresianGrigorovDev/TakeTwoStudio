<?php

namespace App\Filament\Resources\PortraitPortfolioPhotoResource\Pages;

use App\Filament\Resources\PortraitPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPortraitPortfolioPhotos extends ListRecords
{
    protected static string $resource = PortraitPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

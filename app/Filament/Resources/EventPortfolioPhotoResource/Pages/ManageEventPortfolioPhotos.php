<?php

namespace App\Filament\Resources\EventPortfolioPhotoResource\Pages;

use App\Filament\Resources\EventPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEventPortfolioPhotos extends ManageRecords
{
    protected static string $resource = EventPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PortraitPortfolioPhotoResource\Pages;

use App\Filament\Resources\PortraitPortfolioPhotoResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions;

class ListPortraitPortfolioPhotos extends AuditedListRecords
{
    protected static string $resource = PortraitPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

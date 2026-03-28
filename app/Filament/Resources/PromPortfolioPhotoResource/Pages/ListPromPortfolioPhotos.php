<?php

namespace App\Filament\Resources\PromPortfolioPhotoResource\Pages;

use App\Filament\Resources\PromPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromPortfolioPhotos extends ListRecords
{
    protected static string $resource = PromPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

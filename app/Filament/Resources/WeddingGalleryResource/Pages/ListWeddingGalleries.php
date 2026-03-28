<?php

namespace App\Filament\Resources\WeddingGalleryResource\Pages;

use App\Filament\Resources\WeddingGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeddingGalleries extends ListRecords
{
    protected static string $resource = WeddingGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\WeddingGalleryResource\Pages;

use App\Filament\Resources\WeddingGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeddingGallery extends EditRecord
{
    protected static string $resource = WeddingGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

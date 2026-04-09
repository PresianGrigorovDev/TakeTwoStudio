<?php

namespace App\Filament\Resources\PortraitGalleryResource\Pages;

use App\Filament\Resources\PortraitGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortraitGallery extends EditRecord
{
    protected static string $resource = PortraitGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AutomotiveGalleryResource\Pages;

use App\Filament\Resources\AutomotiveGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutomotiveGallery extends EditRecord
{
    protected static string $resource = AutomotiveGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

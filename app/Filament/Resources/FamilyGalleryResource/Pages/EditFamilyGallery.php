<?php

namespace App\Filament\Resources\FamilyGalleryResource\Pages;

use App\Filament\Resources\FamilyGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFamilyGallery extends EditRecord
{
    protected static string $resource = FamilyGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

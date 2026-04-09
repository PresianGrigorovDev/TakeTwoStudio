<?php

namespace App\Filament\Resources\ArchitecturalGalleryResource\Pages;

use App\Filament\Resources\ArchitecturalGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchitecturalGallery extends EditRecord
{
    protected static string $resource = ArchitecturalGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

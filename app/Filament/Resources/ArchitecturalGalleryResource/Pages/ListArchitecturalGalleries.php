<?php

namespace App\Filament\Resources\ArchitecturalGalleryResource\Pages;

use App\Filament\Resources\ArchitecturalGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArchitecturalGalleries extends ListRecords
{
    protected static string $resource = ArchitecturalGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

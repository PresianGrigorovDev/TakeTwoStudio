<?php

namespace App\Filament\Resources\AutomotiveGalleryResource\Pages;

use App\Filament\Resources\AutomotiveGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutomotiveGalleries extends ListRecords
{
    protected static string $resource = AutomotiveGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

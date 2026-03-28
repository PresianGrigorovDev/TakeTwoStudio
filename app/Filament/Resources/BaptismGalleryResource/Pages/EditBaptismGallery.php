<?php

namespace App\Filament\Resources\BaptismGalleryResource\Pages;

use App\Filament\Resources\BaptismGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBaptismGallery extends EditRecord
{
    protected static string $resource = BaptismGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

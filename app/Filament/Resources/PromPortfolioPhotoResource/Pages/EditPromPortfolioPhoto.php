<?php

namespace App\Filament\Resources\PromPortfolioPhotoResource\Pages;

use App\Filament\Resources\PromPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromPortfolioPhoto extends EditRecord
{
    protected static string $resource = PromPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

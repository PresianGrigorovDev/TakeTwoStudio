<?php

namespace App\Filament\Resources\GraduationPortfolioPhotoResource\Pages;

use App\Filament\Resources\GraduationPortfolioPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGraduationPortfolioPhoto extends EditRecord
{
    protected static string $resource = GraduationPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}

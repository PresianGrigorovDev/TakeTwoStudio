<?php

namespace App\Filament\Resources\GraduationPortfolioPhotoResource\Pages;

use App\Filament\Resources\GraduationPortfolioPhotoResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions;

class ListGraduationPortfolioPhotos extends AuditedListRecords
{
    protected static string $resource = GraduationPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

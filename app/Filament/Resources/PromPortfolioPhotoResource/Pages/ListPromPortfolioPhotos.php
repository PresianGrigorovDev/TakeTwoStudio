<?php

namespace App\Filament\Resources\PromPortfolioPhotoResource\Pages;

use App\Filament\Resources\PromPortfolioPhotoResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions;

class ListPromPortfolioPhotos extends AuditedListRecords
{
    protected static string $resource = PromPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

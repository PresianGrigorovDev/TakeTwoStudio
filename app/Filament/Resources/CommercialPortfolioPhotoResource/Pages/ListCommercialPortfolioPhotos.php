<?php

namespace App\Filament\Resources\CommercialPortfolioPhotoResource\Pages;

use App\Filament\Resources\CommercialPortfolioPhotoResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions;

class ListCommercialPortfolioPhotos extends AuditedListRecords
{
    protected static string $resource = CommercialPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

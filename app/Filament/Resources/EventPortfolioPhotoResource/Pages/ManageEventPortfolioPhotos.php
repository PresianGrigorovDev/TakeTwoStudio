<?php

namespace App\Filament\Resources\EventPortfolioPhotoResource\Pages;

use App\Filament\Resources\EventPortfolioPhotoResource;
use App\Filament\Support\AuditedManageRecords;
use Filament\Actions;

class ManageEventPortfolioPhotos extends AuditedManageRecords
{
    protected static string $resource = EventPortfolioPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

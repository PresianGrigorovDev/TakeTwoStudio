<?php

namespace App\Filament\Resources\ServicePromotionResource\Pages;

use App\Filament\Resources\ServicePromotionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServicePromotions extends ListRecords
{
    protected static string $resource = ServicePromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

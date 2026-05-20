<?php

namespace App\Filament\Resources\ServicePromotionResource\Pages;

use App\Filament\Resources\ServicePromotionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServicePromotion extends EditRecord
{
    protected static string $resource = ServicePromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

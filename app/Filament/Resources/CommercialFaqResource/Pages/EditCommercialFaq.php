<?php

namespace App\Filament\Resources\CommercialFaqResource\Pages;

use App\Filament\Resources\CommercialFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommercialFaq extends EditRecord
{
    protected static string $resource = CommercialFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

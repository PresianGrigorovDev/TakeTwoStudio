<?php

namespace App\Filament\Resources\WeddingExtraResource\Pages;

use App\Filament\Resources\WeddingExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWeddingExtras extends ListRecords
{
    protected static string $resource = WeddingExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\CommercialFaqResource\Pages;

use App\Filament\Resources\CommercialFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommercialFaqs extends ListRecords
{
    protected static string $resource = CommercialFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

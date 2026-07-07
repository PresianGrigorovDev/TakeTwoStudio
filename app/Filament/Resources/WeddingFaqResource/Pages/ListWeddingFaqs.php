<?php

namespace App\Filament\Resources\WeddingFaqResource\Pages;

use App\Filament\Resources\WeddingFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWeddingFaqs extends ListRecords
{
    protected static string $resource = WeddingFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

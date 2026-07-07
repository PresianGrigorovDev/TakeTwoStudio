<?php

namespace App\Filament\Resources\BaptismFaqResource\Pages;

use App\Filament\Resources\BaptismFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaptismFaqs extends ListRecords
{
    protected static string $resource = BaptismFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

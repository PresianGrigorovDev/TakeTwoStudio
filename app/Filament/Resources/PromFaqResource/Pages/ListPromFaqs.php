<?php

namespace App\Filament\Resources\PromFaqResource\Pages;

use App\Filament\Resources\PromFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromFaqs extends ListRecords
{
    protected static string $resource = PromFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

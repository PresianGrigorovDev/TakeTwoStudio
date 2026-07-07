<?php

namespace App\Filament\Resources\PromExtraResource\Pages;

use App\Filament\Resources\PromExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromExtras extends ListRecords
{
    protected static string $resource = PromExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

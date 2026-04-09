<?php

namespace App\Filament\Resources\EventExtraResource\Pages;

use App\Filament\Resources\EventExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventExtras extends ListRecords
{
    protected static string $resource = EventExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

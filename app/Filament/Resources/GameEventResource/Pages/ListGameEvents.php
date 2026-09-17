<?php

namespace App\Filament\Resources\GameEventResource\Pages;

use App\Filament\Resources\GameEventResource;
use Filament\Resources\Pages\ListRecords;

class ListGameEvents extends ListRecords
{
    protected static string $resource = GameEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only page, no actions
        ];
    }
}

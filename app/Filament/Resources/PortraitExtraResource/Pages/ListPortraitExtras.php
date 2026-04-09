<?php

namespace App\Filament\Resources\PortraitExtraResource\Pages;

use App\Filament\Resources\PortraitExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPortraitExtras extends ListRecords
{
    protected static string $resource = PortraitExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\AutomotiveExtraResource\Pages;

use App\Filament\Resources\AutomotiveExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutomotiveExtras extends ListRecords
{
    protected static string $resource = AutomotiveExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

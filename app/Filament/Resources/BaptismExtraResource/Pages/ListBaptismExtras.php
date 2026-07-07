<?php

namespace App\Filament\Resources\BaptismExtraResource\Pages;

use App\Filament\Resources\BaptismExtraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBaptismExtras extends ListRecords
{
    protected static string $resource = BaptismExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

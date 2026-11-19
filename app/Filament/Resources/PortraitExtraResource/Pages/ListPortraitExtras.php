<?php

namespace App\Filament\Resources\PortraitExtraResource\Pages;

use App\Filament\Resources\PortraitExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListPortraitExtras extends AuditedListRecords
{
    protected static string $resource = PortraitExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

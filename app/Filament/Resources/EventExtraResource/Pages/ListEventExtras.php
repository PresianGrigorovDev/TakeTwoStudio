<?php

namespace App\Filament\Resources\EventExtraResource\Pages;

use App\Filament\Resources\EventExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListEventExtras extends AuditedListRecords
{
    protected static string $resource = EventExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

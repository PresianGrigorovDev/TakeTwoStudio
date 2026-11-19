<?php

namespace App\Filament\Resources\PromExtraResource\Pages;

use App\Filament\Resources\PromExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListPromExtras extends AuditedListRecords
{
    protected static string $resource = PromExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

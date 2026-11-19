<?php

namespace App\Filament\Resources\WeddingExtraResource\Pages;

use App\Filament\Resources\WeddingExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListWeddingExtras extends AuditedListRecords
{
    protected static string $resource = WeddingExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

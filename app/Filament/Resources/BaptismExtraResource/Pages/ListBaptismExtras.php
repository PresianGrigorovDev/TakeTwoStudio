<?php

namespace App\Filament\Resources\BaptismExtraResource\Pages;

use App\Filament\Resources\BaptismExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListBaptismExtras extends AuditedListRecords
{
    protected static string $resource = BaptismExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\AutomotiveExtraResource\Pages;

use App\Filament\Resources\AutomotiveExtraResource;
use App\Filament\Support\AuditedListRecords;
use Filament\Actions\CreateAction;

class ListAutomotiveExtras extends AuditedListRecords
{
    protected static string $resource = AutomotiveExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

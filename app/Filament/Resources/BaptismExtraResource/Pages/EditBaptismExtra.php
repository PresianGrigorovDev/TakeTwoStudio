<?php

namespace App\Filament\Resources\BaptismExtraResource\Pages;

use App\Filament\Resources\BaptismExtraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBaptismExtra extends EditRecord
{
    protected static string $resource = BaptismExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

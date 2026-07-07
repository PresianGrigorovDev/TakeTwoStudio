<?php

namespace App\Filament\Resources\BaptismFaqResource\Pages;

use App\Filament\Resources\BaptismFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBaptismFaq extends EditRecord
{
    protected static string $resource = BaptismFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

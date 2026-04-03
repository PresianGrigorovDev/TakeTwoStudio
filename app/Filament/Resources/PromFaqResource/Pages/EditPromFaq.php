<?php

namespace App\Filament\Resources\PromFaqResource\Pages;

use App\Filament\Resources\PromFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromFaq extends EditRecord
{
    protected static string $resource = PromFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

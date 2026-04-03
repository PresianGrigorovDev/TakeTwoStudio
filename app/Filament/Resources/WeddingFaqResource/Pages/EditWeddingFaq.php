<?php

namespace App\Filament\Resources\WeddingFaqResource\Pages;

use App\Filament\Resources\WeddingFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWeddingFaq extends EditRecord
{
    protected static string $resource = WeddingFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

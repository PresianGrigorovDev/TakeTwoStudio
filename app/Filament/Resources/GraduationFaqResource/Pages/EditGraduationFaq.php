<?php

namespace App\Filament\Resources\GraduationFaqResource\Pages;

use App\Filament\Resources\GraduationFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGraduationFaq extends EditRecord
{
    protected static string $resource = GraduationFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

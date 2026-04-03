<?php

namespace App\Filament\Resources\GraduationFaqResource\Pages;

use App\Filament\Resources\GraduationFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGraduationFaqs extends ListRecords
{
    protected static string $resource = GraduationFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

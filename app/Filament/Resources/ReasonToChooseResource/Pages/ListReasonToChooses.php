<?php

namespace App\Filament\Resources\ReasonToChooseResource\Pages;

use App\Filament\Resources\ReasonToChooseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReasonToChooses extends ListRecords
{
    protected static string $resource = ReasonToChooseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

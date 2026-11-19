<?php

namespace App\Filament\Resources\UtilityBillResource\Pages;

use App\Filament\Resources\UtilityBillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUtilityBills extends ListRecords
{
    protected static string $resource = UtilityBillResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Нова битова сметка')];
    }
}

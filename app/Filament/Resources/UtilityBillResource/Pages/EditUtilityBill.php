<?php

namespace App\Filament\Resources\UtilityBillResource\Pages;

use App\Filament\Resources\UtilityBillResource;
use App\Filament\Support\FinanceForms;
use App\Support\Finance\UtilityBills;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUtilityBill extends EditRecord
{
    protected static string $resource = UtilityBillResource::class;

    protected static ?string $title = 'Редакция на битова сметка';

    protected ?bool $hasDatabaseTransactions = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return UtilityBills::formData($this->record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return FinanceForms::save(fn () => app(UtilityBills::class)->update($record, $data, auth()->user()));
    }

    protected function getRedirectUrl(): string
    {
        return UtilityBillResource::balanceUrl($this->record);
    }
}

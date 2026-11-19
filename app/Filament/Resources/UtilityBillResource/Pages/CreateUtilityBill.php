<?php

namespace App\Filament\Resources\UtilityBillResource\Pages;

use App\Filament\Resources\UtilityBillResource;
use App\Filament\Support\FinanceForms;
use App\Support\Finance\UtilityBills;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUtilityBill extends CreateRecord
{
    protected static string $resource = UtilityBillResource::class;

    protected static bool $canCreateAnother = false;

    protected ?bool $hasDatabaseTransactions = false;

    protected static ?string $title = 'Нова битова сметка';

    protected function handleRecordCreation(array $data): Model
    {
        return FinanceForms::save(fn () => app(UtilityBills::class)->create($data, auth()->user()));
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Запиши сметката');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Отказ');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Сметката е записана';
    }

    protected function getRedirectUrl(): string
    {
        return UtilityBillResource::balanceUrl($this->record);
    }
}

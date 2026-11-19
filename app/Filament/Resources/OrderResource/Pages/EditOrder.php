<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\FinanceProjectResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Support\OrderPaymentAction;
use App\Models\FinanceProject;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('shoots')->label('Заснемания')->icon('heroicon-o-camera')->color('gray')
                ->url(fn () => OrderResource::getUrl('shoots', ['record' => $this->record])),
            OrderPaymentAction::onOrder(Actions\Action::make('recordPayment')),
            Actions\Action::make('finance')->label('Финанси')->icon('heroicon-m-banknotes')->color('gray')
                ->visible(fn () => FinanceProject::installed())
                ->url(fn () => FinanceProjectResource::urlForSource($this->record)),
            Actions\DeleteAction::make(),
        ];
    }
}

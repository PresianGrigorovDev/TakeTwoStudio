<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\FinanceProjectResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Support\OrderPaymentAction;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Support\OrderCalendar;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')->label('Основен ангажимент')->icon('heroicon-o-calendar-days')->color('gray')
                ->visible(fn () => OrderResource::bookingUrl($this->record) !== null)
                ->url(fn () => OrderResource::bookingUrl($this->record)),
            Actions\Action::make('shoots')->label('Заснемания')->icon('heroicon-o-camera')->color('gray')
                ->url(fn () => OrderResource::getUrl('shoots', ['record' => $this->record])),
            OrderPaymentAction::onOrder(Actions\Action::make('recordPayment')),
            Actions\Action::make('finance')->label('Финанси')->icon('heroicon-m-banknotes')->color('gray')
                ->visible(fn () => FinanceProject::installed())
                ->url(fn () => FinanceProjectResource::urlForSource($this->record)),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        Order::query()->lockForUpdate()->findOrFail($this->record->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return BookingResource::normalizeTimes($data);
    }

    protected function afterSave(): void
    {
        $this->record->unsetRelation('booking');
        if (! $this->record->booking && ! $this->record->wasChanged(['event_date', 'start_time', 'end_time', 'status'])) {
            return;
        }
        if ($booking = OrderCalendar::fromOrder($this->record)) {
            BookingResource::notifyConflicts($booking);
        }
    }
}

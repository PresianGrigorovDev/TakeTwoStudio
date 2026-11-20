<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Pages\Concerns\ReturnsToCalendar;
use App\Filament\Resources\FinanceProjectResource;
use App\Filament\Resources\OrderResource;
use App\Models\Booking;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Support\OrderCalendar;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditBooking extends EditRecord
{
    use ReturnsToCalendar;

    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('order')->label('Към поръчката')->icon('heroicon-o-rectangle-stack')->color('gray')
                ->visible(fn () => $this->orderUrl() !== null)
                ->url($this->orderUrl(...)),
            Actions\Action::make('useAsPrimary')->label('Използвай като основен')->icon('heroicon-o-link')->color('gray')
                ->visible(fn () => $this->record->kind === Booking::KIND_CLIENT
                    && ! $this->record->is_primary_for_order
                    && ! $this->record->orderShoot
                    && $this->record->order && ! $this->record->order->booking)
                ->requiresConfirmation()
                ->modalDescription('Датата, часът и екипът ще се пренесат в поръчката.')
                ->databaseTransaction()
                ->action(function () {
                    $this->record->refresh();
                    OrderCalendar::fromBooking($this->record, mayBecomePrimary: true);
                }),
            Actions\Action::make('shoots')->label('Заснемания')->icon('heroicon-o-camera')->color('gray')
                ->visible(fn () => $this->shootsUrl() !== null)
                ->url($this->shootsUrl(...)),
            Actions\Action::make('finance')->label('Финанси')->icon('heroicon-m-banknotes')->color('gray')
                ->visible(fn () => FinanceProject::installed() && Booking::isClientKind($this->record->kind))
                ->url(fn () => FinanceProjectResource::urlForSource($this->record)),
            BookingResource::addToCalendarActions(Actions\ActionGroup::class, Actions\Action::class)
                ->button()
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $orderIds = array_filter([$this->record->order_id, filter_var($this->data['order_id'] ?? null, FILTER_VALIDATE_INT)]);
        Order::query()->whereKey($orderIds)->orderBy('id')->lockForUpdate()->get(['id']);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $booking = Booking::query()->lockForUpdate()->findOrFail($this->record->id);
        if ($booking->is_primary_for_order) {
            $data['kind'] = Booking::KIND_CLIENT;
            $data['order_id'] = $booking->order_id;
        }
        $shoot = $booking->orderShoot()->lockForUpdate()->first();
        if ($shoot !== null) {
            if (! in_array($data['kind'] ?? null, Booking::CLIENT_KINDS, true)) {
                throw ValidationException::withMessages(['data.kind' => 'Изберете ангажимент или фотосесия за този запис.']);
            }

            $orderId = $data['order_id'] ?? null;
            if (filled($orderId) && filter_var($orderId, FILTER_VALIDATE_INT) !== (int) $shoot->order_id) {
                throw ValidationException::withMessages(['data.order_id' => 'Изберете поръчката на заснемането.']);
            }
        }

        return BookingResource::normalizeData($data);
    }

    protected function afterSave(): void
    {
        OrderCalendar::fromBooking($this->record, mayBecomePrimary: $this->record->wasChanged('order_id'));
        BookingResource::notifyConflicts($this->record);
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->rememberCalendar();

        $this->calendarReturnUrl = match (request()->query('from')) {
            'order-shoot' => $this->shootsUrl(),
            'order' => $this->orderUrl(),
            default => $this->calendarReturnUrl,
        };
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->calendarReturnUrl ?? parent::getRedirectUrl();
    }

    private function orderUrl(): ?string
    {
        $order = $this->record->order ?? $this->record->orderShoot?->order;

        return $order && OrderResource::canEdit($order)
            ? OrderResource::getUrl('edit', ['record' => $order])
            : null;
    }

    private function shootsUrl(): ?string
    {
        $order = $this->record->orderShoot?->order ?? $this->record->order;

        return $order && OrderResource::canEdit($order) && $order->shoots()->exists()
            ? OrderResource::getUrl('shoots', ['record' => $order])
            : null;
    }
}

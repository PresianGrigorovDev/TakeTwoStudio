<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\FinanceMovementResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Support\OrderPaymentAction;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Support\Finance\OrderPayments;
use App\Support\OrderCalendar;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BookingResource::normalizeTimes($data);
    }

    protected function afterCreate(): void
    {
        if ($booking = OrderCalendar::fromOrder($this->record)) {
            BookingResource::notifyConflicts($booking);
        }
    }

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();
        array_splice($actions, 1, 0, [$this->createWithPaymentAction()]);

        return $actions;
    }

    public function createWithPaymentAction(): Action
    {
        return Action::make('createWithPayment')->label('Създай и запиши плащане')->icon('heroicon-m-banknotes')->color('gray')
            ->visible(fn () => FinanceProject::installed())->databaseTransaction(false)
            ->modalHeading('Клиентско плащане към новата поръчка')
            ->modalSubmitActionLabel('Създай и запиши плащане')
            ->form(OrderPaymentAction::fields())
            ->mountUsing(function (Form $form) {
                $this->authorizeAccess();
                $this->form->getState();
                $form->fill();
            })
            ->action(function (array $data, Action $action) {
                $this->authorizeAccess();
                $orderData = $this->mutateFormDataBeforeCreate($this->form->getState());

                try {
                    $payment = app(OrderPayments::class)->record(function () use ($orderData): Order {
                        $order = $this->handleRecordCreation($orderData);
                        $this->form->model($order)->saveRelationships();

                        return $order;
                    }, $data, auth()->user());
                } catch (ValidationException $exception) {
                    $this->form->model(Order::class);
                    OrderPaymentAction::showError($exception, $action);

                    return;
                }

                $this->record = $payment->project->order;
                Notification::make()->title('Поръчката и плащането са записани')->success()->send();
                $this->redirect(FinanceMovementResource::getUrl('view', ['record' => $payment]));
            });
    }
}

<?php

namespace App\Filament\Support;

use App\Filament\Resources\FinanceMovementResource;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Support\Finance\Ledger;
use App\Support\Finance\OrderPayments;
use Filament\Actions\MountableAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

final class OrderPaymentAction
{
    public static function onOrder(MountableAction $action): MountableAction
    {
        return $action
            ->label(fn (Order $record) => in_array($record->status, ['new', 'contacted'], true) ? 'Приеми и запиши плащане' : 'Запиши плащане')
            ->visible(fn (Order $record) => FinanceProject::installed() && $record->status !== 'cancelled')
            ->icon('heroicon-m-banknotes')
            ->modalHeading('Клиентско плащане')
            ->modalSubmitActionLabel('Запиши плащането')
            ->databaseTransaction(false)
            ->form(fn (Order $record) => self::fields($record))
            ->action(function (Order $record, array $data, MountableAction $action) {
                try {
                    $payment = app(OrderPayments::class)->record($record, $data, auth()->user(), accept: true);
                } catch (ValidationException $exception) {
                    self::showError($exception, $action);

                    return;
                }

                Notification::make()->title('Плащането е записано')->success()->send();
                $action->getLivewire()->redirect(FinanceMovementResource::getUrl('view', ['record' => $payment]));
            });
    }

    public static function fields(?Order $order = null): array
    {
        $existing = $order ? FinanceProject::forOrder($order) : null;
        $hasPayment = $existing && $existing->movements()->posted()->where('kind', FinanceMovement::INCOME)->exists();

        return [Forms\Components\Grid::make(2)->schema([
            FinanceForms::amount('amount', 'Получена сума')->required()->live(onBlur: true),
            Forms\Components\DatePicker::make('occurred_on')->label('Дата на плащане')
                ->default(Ledger::today())->maxDate(Ledger::today())->required(),
            Forms\Components\Select::make('to_account_id')->label('Кой получи парите')
                ->options(fn () => FinanceAccount::choices())->required()->live(),
            Forms\Components\Select::make('handled_by_id')->label('Кой прие плащането по фирмената сметка')
                ->options(fn () => FinanceAccount::choices(true))
                ->visible(fn (Forms\Get $get) => FinanceAccount::canonicalId((int) $get('to_account_id')) === FinanceAccount::companyId()),
            Forms\Components\Select::make('method')->label('Начин на плащане')->options(FinanceMovement::METHODS)->required(),
            Forms\Components\Select::make('payment_stage')->label('Етап на плащането')
                ->options(FinanceMovement::STAGES)->default($hasPayment ? 'installment' : 'deposit')->required(),
            ...FinanceForms::companyFields(receiptOnly: true),
        ])];
    }

    public static function showError(ValidationException $exception, MountableAction $action): void
    {
        Notification::make()->title($exception->getMessage())->danger()->send();
        $action->halt();
    }
}

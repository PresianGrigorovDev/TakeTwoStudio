<?php

namespace App\Filament\Support;

use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Support\Finance\CompanyReserve;
use App\Support\Finance\Euro;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Validation\ValidationException;

final class FinanceForms
{
    public static function amount(string $name, string $label, string $suffix = '€'): TextInput
    {
        return TextInput::make($name)->label($label)->suffix($suffix)
            ->inputMode('decimal')->maxLength(11)
            ->regex('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D');
    }

    public static function save(callable $write): mixed
    {
        try {
            return $write();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors['data.'.$field] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }

    public static function companyFields(bool $receiptOnly = false): array
    {
        $isIncome = fn (Get $get) => $receiptOnly || $get('kind') === FinanceMovement::INCOME;

        return [
            self::amount('company_percentage', 'Процент за фирмената сметка', '%')
                ->default('0')->maxLength(6)->live(onBlur: true)->visible($isIncome),
            Placeholder::make('company_reserve_preview')->label('За фирмената сметка')
                ->content(fn (Get $get) => self::reservePreview($get))
                ->visible(fn (Get $get) => $isIncome($get) && filled($get('amount')) && filled($get('company_percentage'))),
            ToggleButtons::make('company_deposit')->label('Внасяне на фирмения дял')->inline()
                ->options(['pending' => 'За внасяне', 'now' => 'Внесено сега'])->default('pending')
                ->visible(fn (Get $get) => $isIncome($get) && filled($get('to_account_id'))
                    && FinanceAccount::canonicalId((int) $get('to_account_id')) !== FinanceAccount::companyId()
                    && (float) str_replace(',', '.', (string) $get('company_percentage')) > 0),
        ];
    }

    private static function reservePreview(Get $get): string
    {
        try {
            return Euro::format(CompanyReserve::cents(Euro::parse($get('amount')), CompanyReserve::basisPoints($get('company_percentage'))));
        } catch (ValidationException $exception) {
            return $exception->getMessage();
        }
    }
}

<?php

namespace App\Support\Finance;

use Illuminate\Validation\ValidationException;

final class Euro
{
    public static function parse(mixed $amount, string $field = 'amount', bool $allowZero = false): int
    {
        if (! is_scalar($amount) || ! preg_match('/^([0-9]{1,8})(?:[.,]([0-9]{1,2}))?$/D', trim((string) $amount), $parts)) {
            throw ValidationException::withMessages([$field => 'Въведете сума в евро с до два знака след десетичния разделител.']);
        }

        $cents = (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
        if ($cents === 0 && ! $allowZero) {
            throw ValidationException::withMessages([$field => 'Въведете сума над 0 €.']);
        }

        return $cents;
    }

    public static function decimal(int $cents, string $separator = '.'): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).$separator.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function format(int $cents): string
    {
        return self::decimal($cents, ',').' €';
    }
}

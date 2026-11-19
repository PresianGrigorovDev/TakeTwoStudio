<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
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
}

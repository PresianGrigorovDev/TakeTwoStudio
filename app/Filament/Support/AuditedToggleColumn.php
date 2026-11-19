<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\ToggleColumn;

class AuditedToggleColumn extends ToggleColumn
{
    public function updateState(mixed $state): mixed
    {
        // Inline edits run outside Filament's form and action transactions.
        return $this->getRecord()->getConnection()->transaction(fn () => parent::updateState($state));
    }
}

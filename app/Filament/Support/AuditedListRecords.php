<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\ListRecords;

abstract class AuditedListRecords extends ListRecords
{
    public function reorderTable(array $order): void
    {
        TableReorderAudit::run($this->getTable(), $order, parent::reorderTable(...));
    }
}

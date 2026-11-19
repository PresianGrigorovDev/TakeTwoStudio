<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\ManageRecords;

abstract class AuditedManageRecords extends ManageRecords
{
    public function reorderTable(array $order): void
    {
        TableReorderAudit::run($this->getTable(), $order, parent::reorderTable(...));
    }
}

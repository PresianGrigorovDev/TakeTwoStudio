<?php

namespace App\Filament\Support;

use App\Observers\AdminActivityObserver;
use Closure;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class TableReorderAudit
{
    public static function run(Table $table, array $order, Closure $reorder): void
    {
        if ($order === [] || ! $table->isReorderable() || ! AdminActivityObserver::actor()) {
            return;
        }

        $order = array_values($order);
        $query = $table->getQuery()->whereKey($order);
        $model = $query->getModel();
        $column = Str::afterLast((string) $table->getReorderColumn(), '.');

        // Native table reordering performs a bulk update without firing model events.
        $model->getConnection()->transaction(function () use ($query, $model, $column, $order, $reorder) {
            $before = (clone $query)->reorder($model->getQualifiedKeyName())->lockForUpdate()->get()->keyBy($model->getKeyName());

            $reorder($order);

            $observer = app(AdminActivityObserver::class);
            foreach ($query->get() as $record) {
                $old = $before->get($record->getKey())?->getRawOriginal($column);
                $new = $record->getRawOriginal($column);

                if ($old !== $new) {
                    $observer->changes($record, [$column => ['old' => $old, 'new' => $new]], 'Променена е подредбата');
                }
            }
        });
    }
}

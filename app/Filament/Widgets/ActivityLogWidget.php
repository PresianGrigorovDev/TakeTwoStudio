<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActivityLogWidget extends BaseWidget
{
    protected static ?int $sort = 99; // Large sort value to place it at the bottom

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Последна активност (Дневник на промените)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityLog::query()->whereNotNull('user_id')->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата и час')
                    ->dateTime('d.m.Y H:i:s'),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Администратор'),
                Tables\Columns\TextColumn::make('action')
                    ->label('Действие')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Създаване',
                        'updated' => 'Редакция',
                        'deleted' => 'Изтриване',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание'),
            ]);
    }
}

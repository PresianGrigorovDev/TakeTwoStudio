<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $navigationGroup = 'Система';
    protected static ?string $navigationLabel = 'Дневник на промените';
    protected static ?string $pluralModelLabel = 'Дневник на промените';
    protected static ?string $modelLabel = 'Запис';
    protected static ?int $navigationSort = 999;

    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('created_at')
                    ->label('Дата и час')
                    ->disabled(),
                Forms\Components\TextInput::make('user_name')
                    ->label('Администратор')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->label('Действие')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->label('Описание')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('properties')
                    ->label('Променени полета (Старо -> Ново)')
                    ->valueSerializer(function ($value) {
                        if (is_array($value) && isset($value['old']) && isset($value['new'])) {
                            return 'От: "' . (is_array($value['old']) ? json_encode($value['old'], JSON_UNESCAPED_UNICODE) : $value['old']) . '" ➜ Към: "' . (is_array($value['new']) ? json_encode($value['new'], JSON_UNESCAPED_UNICODE) : $value['new']) . '"';
                        }
                        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                    })
                    ->disabled()
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record && !empty($record->properties)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата и час')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Администратор')
                    ->searchable()
                    ->sortable(),
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Действие')
                    ->options([
                        'created' => 'Създаване',
                        'updated' => 'Редакция',
                        'deleted' => 'Изтриване',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Преглед')
                    ->modalHeading('Подробности за промяната'),
            ])
            ->bulkActions([
                // Read-only resource, no bulk actions permitted
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}

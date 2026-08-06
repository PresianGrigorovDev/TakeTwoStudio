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
                Forms\Components\Placeholder::make('properties')
                    ->label('Променени полета (Старо ➜ Ново)')
                    ->content(function ($record) {
                        if (!$record || empty($record->properties)) {
                            return 'Няма променени полета.';
                        }

                        $html = '<div style="display: flex; flex-direction: column; gap: 0.5rem; font-family: monospace;">';
                        foreach ($record->properties as $field => $change) {
                            $old = is_array($change['old']) ? json_encode($change['old'], JSON_UNESCAPED_UNICODE) : $change['old'];
                            $new = is_array($change['new']) ? json_encode($change['new'], JSON_UNESCAPED_UNICODE) : $change['new'];

                            $fieldName = ucwords(str_replace('_', ' ', $field));

                            $html .= "<div><strong>{$fieldName}</strong>: <span style='color: #ef4444; text-decoration: line-through;'>\"{$old}\"</span> <span style='color: #9ca3af;'>➜</span> <span style='color: #22c55e;'>\"{$new}\"</span></div>";
                        }
                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('user_id');
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

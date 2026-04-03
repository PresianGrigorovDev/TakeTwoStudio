<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedDateResource\Pages;
use App\Models\BlockedDate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlockedDateResource extends Resource
{
    protected static ?string $model = BlockedDate::class;
    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationGroup = 'Резервации';
    protected static ?string $navigationLabel = 'Блокирани дати';
    protected static ?string $modelLabel = 'Блокирана дата';
    protected static ?string $pluralModelLabel = 'Блокирани дати';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label('Дата')
                ->required()
                ->unique(ignoreRecord: true)
                ->minDate(now()),
            Forms\Components\CheckboxList::make('blocked_hours')
                ->label('Блокирани часове (оставете празно за цял ден)')
                ->options(fn () => array_combine(
                    \App\Http\Controllers\BookingController::getWorkingHours(),
                    \App\Http\Controllers\BookingController::getWorkingHours()
                ))
                ->columns(4),
            Forms\Components\TextInput::make('reason')
                ->label('Причина')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('blocked_hours')
                    ->label('Часове')
                    ->formatStateUsing(fn ($state, $record) => $record->isFullDayBlocked() ? 'Цял ден' : implode(', ', $record->blocked_hours)),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Причина'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадена')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlockedDates::route('/'),
            'create' => Pages\CreateBlockedDate::route('/create'),
            'edit' => Pages\EditBlockedDate::route('/{record}/edit'),
        ];
    }
}

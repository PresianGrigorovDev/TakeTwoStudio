<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingPackageResource\Pages;
use App\Models\WeddingPackage;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class WeddingPackageResource extends Resource
{
    protected static ?string $model = WeddingPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Сватби';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralModelLabel = 'Пакети и цени';
    protected static ?string $modelLabel = 'Пакет';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Наименование на пакета')
                ->required(),

            TextInput::make('price_eur')
                ->label('Цена (€ EUR)')
                ->numeric()
                ->required()
                ->suffix('€'),

            TextInput::make('price')
                ->label('Цена (лв.) - за справка')
                ->numeric()
                ->required()
                ->suffix('лв.'),

            TextInput::make('description')
                ->label('Кратко описание')
                ->placeholder('напр. Пълна сесия с повече локации')
                ->columnSpanFull(),

            Repeater::make('features')
                ->label('Включено в пакета')
                ->simple(
                    TextInput::make('feature')->required()
                )
                ->addActionLabel('Добави ред')
                ->reorderable()
                ->columnSpanFull(),

            TextInput::make('sort_order')
                ->label('Наредба')
                ->numeric()
                ->default(0),

            Toggle::make('is_featured')
                ->label('Препоръчан (highlighted)')
                ->default(false),

            Toggle::make('is_visible')
                ->label('Видим')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                TextColumn::make('name')
                    ->label('Пакет')
                    ->searchable(),

                TextColumn::make('price_eur')
                    ->label('Цена')
                    ->prefix('€ ')
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('Препоръчан')
                    ->boolean(),

                IconColumn::make('is_visible')
                    ->label('Видим')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWeddingPackages::route('/'),
            'create' => Pages\CreateWeddingPackage::route('/create'),
            'edit'   => Pages\EditWeddingPackage::route('/{record}/edit'),
        ];
    }
}

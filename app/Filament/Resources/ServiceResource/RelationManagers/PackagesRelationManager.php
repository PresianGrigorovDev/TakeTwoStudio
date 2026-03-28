<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    protected static ?string $title = 'Ценови Пакети';
    protected static ?string $modelLabel = 'Пакет';
    protected static ?string $pluralModelLabel = 'Пакети';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_bg')
                    ->label('Име на пакета')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price_eur')
                    ->label('Цена (EUR)')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Описание (БГ)')
                    ->placeholder('За нов ред използвайте Enter')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('icon_class')
                    ->label('Икона (FontAwesome)')
                    ->placeholder('fa-star')
                    ->maxLength(50),
                Forms\Components\Toggle::make('is_default')
                    ->label('По подразбиране?')
                    ->required(),
                Forms\Components\TextInput::make('display_order')
                    ->label('Поредност')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_bg')
            ->columns([
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_eur')
                    ->label('Цена (€)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('По подразбиране')
                    ->boolean(),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Поредност')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
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
}

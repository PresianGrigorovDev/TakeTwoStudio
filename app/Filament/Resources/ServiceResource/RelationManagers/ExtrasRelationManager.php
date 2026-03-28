<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtrasRelationManager extends RelationManager
{
    protected static string $relationship = 'extras';

    protected static ?string $title = 'Добавки / Екстри';
    protected static ?string $modelLabel = 'Добавка';
    protected static ?string $pluralModelLabel = 'Добавки';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label_bg')
                    ->label('Заглавие')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price_eur')
                    ->label('Цена (EUR)')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\Select::make('input_type')
                    ->label('Тип избор')
                    ->options([
                        'checkbox' => 'Квадратче (Checkbox)',
                        'radio' => 'Бутон (Radio)',
                        'number' => 'Число',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('group_name_bg')
                    ->label('Група (напр. Фото Добавки)')
                    ->maxLength(100),
                Forms\Components\TextInput::make('description_bg')
                    ->label('Кратко описание')
                    ->maxLength(255),
                Forms\Components\TextInput::make('icon_class')
                    ->label('Икона (FontAwesome)')
                    ->placeholder('fa-star')
                    ->maxLength(50),
                Forms\Components\Toggle::make('is_default')
                    ->label('По подразбиране?'),
                Forms\Components\TextInput::make('display_order')
                    ->label('Поредност')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label_bg')
            ->columns([
                Tables\Columns\TextColumn::make('label_bg')
                    ->label('Заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_eur')
                    ->label('Цена (€)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('group_name_bg')
                    ->label('Група'),
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

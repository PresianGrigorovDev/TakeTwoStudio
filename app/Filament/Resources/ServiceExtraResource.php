<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceExtraResource\Pages;
use App\Filament\Resources\ServiceExtraResource\RelationManagers;
use App\Models\ServiceExtra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceExtraResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Добавки на услуги';
    protected static ?string $pluralModelLabel = 'Добавки на услуги';
    protected static ?string $modelLabel = 'Добавка на услуга';
    protected static ?int $navigationSort = 3;

    protected static ?string $model = ServiceExtra::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name_bg')
                    ->required(),
                Forms\Components\TextInput::make('label_bg')
                    ->label('Label (BG)')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price_eur')
                    ->label('Price (EUR)')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\Select::make('input_type')
                    ->options([
                        'checkbox' => 'Checkbox',
                        'radio' => 'Radio Button',
                        'number' => 'Number Input',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('group_name_bg')
                    ->label('Group Name (BG)')
                    ->maxLength(100),
                Forms\Components\TextInput::make('description_bg')
                    ->label('Description (BG)')
                    ->maxLength(255),
                Forms\Components\TextInput::make('icon_class')
                    ->label('Icon Class (FontAwesome)')
                    ->placeholder('fa-star')
                    ->maxLength(50),
                Forms\Components\Toggle::make('is_default')
                    ->label('Default?'),
                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name_bg')
                    ->label('Service')
                    ->sortable(),
                Tables\Columns\TextColumn::make('label_bg')
                    ->label('Label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_eur')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('input_type'),
                Tables\Columns\TextColumn::make('group_name_bg')
                    ->label('Group'),
                Tables\Columns\TextColumn::make('icon_class')
                    ->label('Icon'),
                Tables\Columns\ToggleColumn::make('is_default')
                    ->label('Default'),
                Tables\Columns\TextColumn::make('display_order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service')
                    ->relationship('service', 'name_bg'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListServiceExtras::route('/'),
            'create' => Pages\CreateServiceExtra::route('/create'),
            'edit' => Pages\EditServiceExtra::route('/{record}/edit'),
        ];
    }
}

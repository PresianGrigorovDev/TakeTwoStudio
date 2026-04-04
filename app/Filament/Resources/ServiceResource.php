<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Услуги';
    protected static ?string $pluralModelLabel = 'Услуги';
    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_bg')
                    ->label('Име (БГ)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('Слъг (URL)')
                    ->required()
                    ->disabled()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Описание (БГ)')
                    ->columnSpanFull(),
                IconPicker::make('icon_class')
                    ->label('Икона')
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\ImageColumn::make('icon')
                //     ->label('Икона'),
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слъг (Пресо му разбира)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            RelationManagers\PackagesRelationManager::class,
            RelationManagers\ExtrasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NavigationItemResource\Pages;
use App\Filament\Resources\NavigationItemResource\RelationManagers;
use App\Models\NavigationItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NavigationItemResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Меню Навигация';
    protected static ?string $pluralModelLabel = 'Елементи в менюто';
    protected static ?string $modelLabel = 'Елемент от менюто';

    protected static ?string $model = NavigationItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label_bg')
                    ->label('Заглавие (БГ)')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('link_url')
                    ->label('Линк / URL')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('parent_id')
                    ->label('Родителски елемент')
                    ->relationship('parent', 'label_bg')
                    ->placeholder('Изберете (ако е подменю)'),
                Forms\Components\TextInput::make('display_order')
                    ->label('Поредност')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label_bg')
                    ->label('Заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('link_url')
                    ->label('Линк'),
                Tables\Columns\TextColumn::make('parent.label_bg')
                    ->label('Родител')
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Поредност')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създаден на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновен на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent')
                    ->label('Родител')
                    ->relationship('parent', 'label_bg'),
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
            'index' => Pages\ListNavigationItems::route('/'),
            'create' => Pages\CreateNavigationItem::route('/create'),
            'edit' => Pages\EditNavigationItem::route('/{record}/edit'),
        ];
    }
}

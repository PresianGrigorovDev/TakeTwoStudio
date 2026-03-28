<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaResource\Pages;
use App\Filament\Resources\SocialMediaResource\RelationManagers;
use App\Models\SocialMedia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SocialMediaResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Социални мрежи';
    protected static ?string $pluralModelLabel = 'Социални мрежи';
    protected static ?string $modelLabel = 'Социална мрежа';

    protected static ?string $model = SocialMedia::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('platform_name')
                    ->label('Платформа (напр. Facebook)')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('url')
                    ->label('Линк (URL)')
                    ->url()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('icon_class')
                    ->label('Икона (FontAwesome)')
                    ->placeholder('fab fa-facebook')
                    ->required()
                    ->maxLength(50),
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
                Tables\Columns\TextColumn::make('platform_name')
                    ->label('Платформа')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('Линк')
                    ->searchable(),
                Tables\Columns\TextColumn::make('icon_class')
                    ->label('Икона'),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Поредност')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадена на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновена на')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialMedia::route('/'),
            'create' => Pages\CreateSocialMedia::route('/create'),
            'edit' => Pages\EditSocialMedia::route('/{record}/edit'),
        ];
    }
}

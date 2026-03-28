<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReasonToChooseResource\Pages;
use App\Filament\Resources\ReasonToChooseResource\RelationManagers;
use App\Models\ReasonToChoose;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReasonToChooseResource extends Resource
{
    protected static ?string $navigationGroup = 'За Нас';
    protected static ?string $navigationLabel = 'Защо да ни изберете';
    protected static ?string $pluralModelLabel = 'Причини за избор';
    protected static ?string $modelLabel = 'Причина';

    protected static ?string $model = ReasonToChoose::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('page_slug')
                    ->label('Страница (slug)')
                    ->maxLength(50),
                Forms\Components\TextInput::make('title_bg')
                    ->label('Заглавие (БГ)')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('icon_class')
                    ->label('Икона (FontAwesome)')
                    ->required()
                    ->maxLength(50),
                Forms\Components\Textarea::make('content_bg')
                    ->label('Описание (БГ)')
                    ->required()
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('title_bg')
                    ->label('Заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('page_slug')
                    ->label('Страница')
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
            'index' => Pages\ListReasonToChooses::route('/'),
            'create' => Pages\CreateReasonToChoose::route('/create'),
            'edit' => Pages\EditReasonToChoose::route('/{record}/edit'),
        ];
    }
}

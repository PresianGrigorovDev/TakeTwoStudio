<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageContentResource\Pages;
use App\Filament\Resources\PageContentResource\RelationManagers;
use App\Models\PageContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageContentResource extends Resource
{
    protected static ?string $navigationGroup = 'Съдържание';
    protected static ?string $navigationLabel = 'Текстове по страници';
    protected static ?string $pluralModelLabel = 'Текстово съдържание';
    protected static ?string $modelLabel = 'Текст';

    protected static ?string $model = PageContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('page_slug')
                    ->label('Страница (slug)')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('section_slug')
                    ->label('Секция (slug)')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('field_key')
                    ->label('Ключ на полето')
                    ->required()
                    ->maxLength(50),
                Forms\Components\Textarea::make('content_bg')
                    ->label('Съдържание (БГ)')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('content_en')
                    ->label('Съдържание (EN)')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('page_slug')
                    ->label('Страница')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section_slug')
                    ->label('Секция')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('field_key')
                    ->label('Ключ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content_bg')
                    ->label('Съдържание (БГ)')
                    ->limit(50),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageContents::route('/'),
            'create' => Pages\CreatePageContent::route('/create'),
            'edit' => Pages\EditPageContent::route('/{record}/edit'),
        ];
    }
}

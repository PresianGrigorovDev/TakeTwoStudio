<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioItemResource\Pages;
use App\Filament\Resources\PortfolioItemResource\RelationManagers;
use App\Models\PortfolioItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PortfolioItemResource extends Resource
{
    // Hide this page
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Портфолио';
    protected static ?string $navigationLabel = 'Елементи';
    protected static ?string $pluralModelLabel = 'Елементи на портфолиото';
    protected static ?string $modelLabel = 'Елемент';

    protected static ?string $model = PortfolioItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name_bg')
                    ->required(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Файл (Снимка/Видео)')
                    ->image()
                    ->directory('portfolio')
                    ->required(),
                Forms\Components\Select::make('item_type')
                    ->label('Тип')
                    ->options([
                        'image' => 'Снимка',
                        'video' => 'Видео',
                    ])
                    ->required()
                    ->default('image'),
                Forms\Components\TextInput::make('alt_text_bg')
                    ->label('Алтернативен текст (БГ)')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Файл'),
                Tables\Columns\TextColumn::make('category.name_bg')
                    ->label('Категория')
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Тип')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'image' => 'Снимка',
                        'video' => 'Видео',
                        default => $state,
                    }),
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
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->relationship('category', 'name_bg'),
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
            'index' => Pages\ListPortfolioItems::route('/'),
            'create' => Pages\CreatePortfolioItem::route('/create'),
            'edit' => Pages\EditPortfolioItem::route('/{record}/edit'),
        ];
    }
}

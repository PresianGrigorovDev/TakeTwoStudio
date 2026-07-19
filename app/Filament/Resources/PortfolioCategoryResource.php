<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioCategoryResource\Pages;
use App\Filament\Resources\PortfolioCategoryResource\RelationManagers;
use App\Models\PortfolioCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Contracts\Editable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class PortfolioCategoryResource extends Resource
{
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?string $navigationLabel = 'Категории';
    protected static ?string $pluralModelLabel = 'Категории портфолио';
    protected static ?string $modelLabel = 'Категория';

    protected static ?string $model = PortfolioCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_bg')
                    ->label('Име (БГ)')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('slug')
                    ->label('Слъг (URL)')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('subtitle_bg')
                    ->label('Подзаглавие (БГ)')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Описание (БГ)')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('cover_image')
                    ->label('Корица')
                    ->required()
                    ->image()
                    ->directory('portfolio/categories'),
                Forms\Components\TextInput::make('display_order')
                    ->label('Поредност')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_visible')
                ->label('Видим')
                ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Корица')
                    ->editable(true),
                Tables\Columns\TextColumn::make('name_bg')
                    ->label('Име')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Слъг')
                    ->searchable(),
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
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Видим'),
            ])
            ->defaultSort('display_order')
            ->reorderable('display_order')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('makeVisible')
                        ->label('Направи видими')
                        ->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_visible' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('makeHidden')
                        ->label('Скрий')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_visible' => false]))
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListPortfolioCategories::route('/'),
            'create' => Pages\CreatePortfolioCategory::route('/create'),
            'edit' => Pages\EditPortfolioCategory::route('/{record}/edit'),
        ];
    }
}

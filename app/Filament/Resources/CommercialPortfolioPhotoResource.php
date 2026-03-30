<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommercialPortfolioPhotoResource\Pages;
use App\Models\CommercialPortfolioPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommercialPortfolioPhotoResource extends Resource
{
    protected static ?string $model = CommercialPortfolioPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Рекламни';
    protected static ?string $modelLabel = 'Снимка - Рекламни';
    protected static ?string $pluralModelLabel = 'Снимки - Рекламни';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->label('Снимка')
                    ->image()
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('1920')
                    ->imageResizeTargetHeight('1080')
                    ->directory('commercial_portfolio_photos')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('sub_category')
                    ->label('Подкатегория')
                    ->options([
                        'ads' => 'Рекламна',
                        'product' => 'Продуктова',
                        'imoti' => 'Имоти',
                        'events' => 'Събития',
                        'drone' => 'Дрон',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(fn () => \App\Models\CommercialPortfolioPhoto::max('sort_order') + 1)
                    ->required(),
                Forms\Components\TextInput::make('alt_text_bg')
                    ->label('Заглавие (БГ)')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description_bg')
                    ->label('Кратко описание (БГ)')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_visible')
                    ->label('Видима')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Снимка')
                    ->height(100),
                Tables\Columns\TextColumn::make('sub_category')
                    ->label('Подкатегория')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'ads' => 'Рекламна',
                        'product' => 'Продуктова',
                        'imoti' => 'Имоти',
                        'events' => 'Събития',
                        'drone' => 'Дрон',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('alt_text_bg')
                    ->label('Заглавие')
                    ->limit(30),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Видима'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('sub_category')
                    ->label('Подкатегория')
                    ->options([
                        'ads' => 'Рекламна',
                        'product' => 'Продуктова',
                        'imoti' => 'Имоти',
                        'events' => 'Събития',
                        'drone' => 'Дрон',
                    ]),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommercialPortfolioPhotos::route('/'),
            'create' => Pages\CreateCommercialPortfolioPhoto::route('/create'),
            'edit' => Pages\EditCommercialPortfolioPhoto::route('/{record}/edit'),
        ];
    }
}

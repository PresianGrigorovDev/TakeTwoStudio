<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommercialPortfolioPhotoResource\Pages;
use App\Models\CommercialPortfolioPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CommercialPortfolioPhotoResource extends Resource
{
    protected static ?string $model = CommercialPortfolioPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $navigationGroup = 'Търговски';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Снимка - Реклама';
    protected static ?string $pluralModelLabel = 'Снимки - Реклама';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('current_image_preview')
                    ->label('Текуща снимка')
                    ->content(fn (?CommercialPortfolioPhoto $record): \Illuminate\Support\HtmlString =>
                        $record
                            ? new \Illuminate\Support\HtmlString(
                                '<img src="' . (str_starts_with($record->image_path ?? '', 'css/')
                                    ? asset($record->image_path)
                                    : Storage::url($record->image_path)
                                ) . '" style="max-height:240px; border-radius:6px; border:2px solid #374151; object-fit:contain;">'
                            )
                            : new \Illuminate\Support\HtmlString('')
                    )
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Качи нова снимка (оставете празно за да запазите текущата)')
                    ->image()
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('1920')
                    ->imageResizeTargetHeight('1080')
                    ->directory('commercial_portfolio_photos')
                    ->disk('public')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('alt_text')
                    ->label('Заглавие / Alt текст')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Описание (SEO + hover текст)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Select::make('sub_category')
                    ->label('Категория')
                    ->options([
                        'ads'     => 'Реклама',
                        'product' => 'Продуктова',
                        'imoti'   => 'Имоти',
                        'events'  => 'Събития',
                        'drone'   => 'Дрон',
                    ])
                    ->default('ads')
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(fn () => \App\Models\CommercialPortfolioPhoto::max('sort_order') + 1)
                    ->required(),
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
                    ->height(100)
                    ->getStateUsing(fn (CommercialPortfolioPhoto $record): string =>
                        str_starts_with($record->image_path, 'css/')
                            ? asset($record->image_path)
                            : Storage::url($record->image_path)
                    ),
                Tables\Columns\TextColumn::make('alt_text')
                    ->label('Заглавие')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('sub_category')
                    ->label('Категория')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ads'     => 'Реклама',
                        'product' => 'Продуктова',
                        'imoti'   => 'Имоти',
                        'events'  => 'Събития',
                        'drone'   => 'Дрон',
                        default   => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Видима'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('sub_category')
                    ->label('Категория')
                    ->options([
                        'ads'     => 'Реклама',
                        'product' => 'Продуктова',
                        'imoti'   => 'Имоти',
                        'events'  => 'Събития',
                        'drone'   => 'Дрон',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCommercialPortfolioPhotos::route('/'),
            'create' => Pages\CreateCommercialPortfolioPhoto::route('/create'),
            'edit'   => Pages\EditCommercialPortfolioPhoto::route('/{record}/edit'),
        ];
    }
}

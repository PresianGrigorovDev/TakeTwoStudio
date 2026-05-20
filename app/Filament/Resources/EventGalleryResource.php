<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventGalleryResource\Pages;
use App\Models\EventGallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EventGalleryResource extends Resource
{
    protected static ?string $model = EventGallery::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationGroup = 'Събитийна Фотография';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Събитийна Галерия';
    protected static ?string $pluralModelLabel = 'Събитийни Галерии';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна Информация')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \App\Support\Transliteration::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('event_date')
                            ->label('Дата на събитието')
                            ->native(false),
                        Forms\Components\Placeholder::make('cover_image_preview')
                            ->label('Текуща корица')
                            ->content(fn (?EventGallery $record): \Illuminate\Support\HtmlString =>
                                $record && $record->cover_image
                                    ? new \Illuminate\Support\HtmlString(
                                        '<img src="' . Storage::url($record->cover_image) . '" style="max-height:200px; border-radius:6px; border:2px solid #374151; object-fit:contain;">'
                                    )
                                    : new \Illuminate\Support\HtmlString('')
                            )
                            ->visibleOn('edit'),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Качи нова корица (оставете празно за да запазите текущата)')
                            ->image()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->directory('event_galleries/covers')
                            ->disk('public')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state)),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Снимки в Галерията')
                    ->description('Може да добавиш до 50 снимки.')
                    ->schema([
                        Forms\Components\Repeater::make('photos')
                            ->label('Снимки')
                            ->relationship()
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Снимка')
                                    ->image()
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->directory('event_galleries/photos')
                                    ->disk('public')
                                    ->required(),
                            ])
                            ->orderColumn('sort_order')
                            ->defaultItems(1)
                            ->maxItems(50)
                            ->grid(3)
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Корица'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Дата')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
            'index' => Pages\ListEventGalleries::route('/'),
            'create' => Pages\CreateEventGallery::route('/create'),
            'edit' => Pages\EditEventGallery::route('/{record}/edit'),
        ];
    }
}

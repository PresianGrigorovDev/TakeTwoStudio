<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromPortfolioPhotoResource\Pages;
use App\Models\PromPortfolioPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PromPortfolioPhotoResource extends Resource
{
    protected static ?string $model = PromPortfolioPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationGroup = 'Абитуриенти';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Снимка - Балове';
    protected static ?string $pluralModelLabel = 'Снимки - Балове';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('current_image_preview')
                    ->label('Текуща снимка')
                    ->content(fn (?PromPortfolioPhoto $record): \Illuminate\Support\HtmlString =>
                        $record
                            ? new \Illuminate\Support\HtmlString(
                                '<img src="' . Storage::url($record->image_path) . '" style="max-height:240px; border-radius:6px; border:2px solid #374151; object-fit:contain;">'
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
                    ->directory('prom_portfolio_photos')
                    ->disk('public')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(fn () => \App\Models\PromPortfolioPhoto::max('sort_order') + 1)
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
                    ->height(100),
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
            'index' => Pages\ListPromPortfolioPhotos::route('/'),
            'create' => Pages\CreatePromPortfolioPhoto::route('/create'),
            'edit' => Pages\EditPromPortfolioPhoto::route('/{record}/edit'),
        ];
    }
}

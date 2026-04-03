<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GraduationPortfolioPhotoResource\Pages;
use App\Models\GraduationPortfolioPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class GraduationPortfolioPhotoResource extends Resource
{
    protected static ?string $model = GraduationPortfolioPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Изпращане';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Снимка - Изпращане';
    protected static ?string $pluralModelLabel = 'Снимки - Изпращане';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('current_image_preview')
                    ->label('Текуща снимка')
                    ->content(fn (?GraduationPortfolioPhoto $record): \Illuminate\Support\HtmlString =>
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
                    ->directory('graduation_portfolio_photos')
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
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(fn () => GraduationPortfolioPhoto::max('sort_order') + 1)
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
                    ->getStateUsing(fn (GraduationPortfolioPhoto $record): string =>
                        Storage::url($record->image_path)
                    ),
                Tables\Columns\TextColumn::make('alt_text')
                    ->label('Заглавие')
                    ->searchable()
                    ->limit(40),
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
            'index'  => Pages\ListGraduationPortfolioPhotos::route('/'),
            'create' => Pages\CreateGraduationPortfolioPhoto::route('/create'),
            'edit'   => Pages\EditGraduationPortfolioPhoto::route('/{record}/edit'),
        ];
    }
}

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
                static::coverUpload(),
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
                    ->label('Корица'),
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
                Tables\Actions\Action::make('change_cover')
                    ->label('Снимка')
                    ->icon('heroicon-o-camera')
                    ->form([
                        static::coverUpload(),
                    ])
                    ->mountUsing(fn ($form, $record) => $form->fill([
                        'cover_image' => $record->cover_image,
                    ]))
                    ->action(function (PortfolioCategory $record, array $data): void {
                        $record->update($data);
                    }),
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

    /**
     * The cover fills a 3:2 card with object-fit: cover, so it is fitted inside
     * 1200x1200 in the browser (no forced crop - the card crops). maxSize is checked
     * by FilePond on the ORIGINAL file before that resize, so it must let 6-11 MB
     * camera photos through; PortfolioCategory also optimises on save.
     */
    private static function coverUpload(): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make('cover_image')
            ->label('Корица')
            ->helperText('Хоризонтална снимка (3:2). Може и директно от фотоапарата - намалява се автоматично до 1200 px.')
            ->required()
            ->image()
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth((string) PortfolioCategory::COVER_MAX)
            ->imageResizeTargetHeight((string) PortfolioCategory::COVER_MAX)
            ->imageResizeUpscale(false)
            ->maxSize(20480)
            ->directory('portfolio/categories');
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

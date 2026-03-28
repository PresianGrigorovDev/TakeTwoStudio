<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaptismGalleryResource\Pages;
use App\Filament\Resources\BaptismGalleryResource\RelationManagers;
use App\Models\BaptismGallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BaptismGalleryResource extends Resource
{
    protected static ?string $model = BaptismGallery::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationGroup = 'Кръщенета';
    protected static ?string $modelLabel = 'Галерия за Кръщене';
    protected static ?string $pluralModelLabel = 'Галерии за Кръщене';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна Информация')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заглавие (напр. Иван и семейство)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('event_date')
                            ->label('Дата на събитието')
                            ->native(false),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Главна снимка (Корица)')
                            ->image()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->directory('baptism_galleries/covers')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Снимки в Галерията')
                    ->description('Можете да добавите до 15 снимки.')
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
                                    ->directory('baptism_galleries/photos')
                                    ->required(),
                            ])
                            ->orderColumn('sort_order')
                            ->defaultItems(1)
                            ->maxItems(15)
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
            'index' => Pages\ListBaptismGalleries::route('/'),
            'create' => Pages\CreateBaptismGallery::route('/create'),
            'edit' => Pages\EditBaptismGallery::route('/{record}/edit'),
        ];
    }
}

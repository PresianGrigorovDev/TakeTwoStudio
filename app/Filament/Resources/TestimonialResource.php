<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Filament\Resources\TestimonialResource\RelationManagers;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TestimonialResource extends Resource
{
    protected static ?string $navigationGroup = 'За Нас';
    protected static ?string $navigationLabel = 'Отзиви';
    protected static ?string $pluralModelLabel = 'Отзиви от клиенти';
    protected static ?string $modelLabel = 'Отзив';

    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client_name')
                    ->label('Име на клиента')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Textarea::make('content_bg')
                    ->label('Текст на отзива (БГ)')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('content_en')
                    ->label('Текст на отзива (EN)')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('rating')
                    ->label('Оценка (1-5)')
                    ->numeric()
                    ->default(5)
                    ->minValue(1)
                    ->maxValue(5)
                    ->required(),
                Forms\Components\DatePicker::make('event_date')
                    ->label('Дата на събитието'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Клиент')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Оценка')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Дата')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}

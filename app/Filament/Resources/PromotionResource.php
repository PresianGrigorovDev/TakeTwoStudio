<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Popup Промоции';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $modelLabel = 'Промоция';
    protected static ?string $pluralModelLabel = 'Промоции';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Банер')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заглавие / Бележка (само за администратора)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('напр. Лятна промоция 2025'),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Банер изображение')
                            ->image()
                            ->imageEditor()
                            ->directory('promotions')
                            ->required()
                            ->helperText('Препоръчителен размер: 800×500px. Цялото изображение е кликаемо.'),

                        Forms\Components\TextInput::make('redirect_url')
                            ->label('Линк при клик (оставете празно ако няма)')
                            ->placeholder('напр. /weddings или /proms')
                            ->maxLength(500),
                    ]),

                Forms\Components\Section::make('Таймер и Промо Код')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Промоцията започва на')
                            ->nullable()
                            ->helperText('Ако е зададено, няма да се показва преди тази дата.'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Промоцията изтича на')
                            ->nullable()
                            ->helperText('Ако е зададено, ще се покаже таймер с обратно броене.'),

                        Forms\Components\Select::make('promo_code_id')
                            ->label('Вързан Промо Код')
                            ->relationship('promoCode', 'code')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->helperText('Промо кодът ще се показва под банера с бутон "Копирай".'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна промоция')
                            ->default(false)
                            ->helperText('Само една промоция може да е активна. Активирайте само тази, която искате да се показва.'),

                        Forms\Components\TextInput::make('popup_days_interval')
                            ->label('Интервал (дни) преди повторно показване')
                            ->numeric()
                            ->default(7)
                            ->minValue(0)
                            ->suffix('дни')
                            ->helperText('След затваряне на popup-а, посетителят няма да го вижда толкова дни.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Банер')
                    ->square()
                    ->width(80)
                    ->height(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('promoCode.code')
                    ->label('Промо Код')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Изтича на')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('popup_days_interval')
                    ->label('Интервал (дни)')
                    ->alignCenter()
                    ->suffix(' дни'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активна'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновена')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit'   => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}

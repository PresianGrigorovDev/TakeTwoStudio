<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Промо Кодове';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $modelLabel = 'Промо Код';
    protected static ?string $pluralModelLabel = 'Промо Кодове';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основни данни')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state))),

                        Forms\Components\TextInput::make('description')
                            ->label('Описание (за администратора)')
                            ->maxLength(255)
                            ->placeholder('напр. Лятна кампания Instagram'),

                        Forms\Components\Select::make('source')
                            ->label('Канал / Произход')
                            ->options([
                                'Instagram'   => 'Instagram',
                                'Facebook'    => 'Facebook',
                                'Email'       => 'Email кампания',
                                'Flyer'       => 'Флаер / Принт',
                                'Partner'     => 'Партньор',
                                'Website'     => 'Уебсайт',
                                'Word of Mouth' => 'Препоръка',
                                'Other'       => 'Друго',
                            ])
                            ->searchable()
                            ->placeholder('Изберете канал'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Отстъпка')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('Тип отстъпка')
                            ->options([
                                'percent'   => '% Процент',
                                'fixed_eur' => '€ Фиксирана сума',
                            ])
                            ->required()
                            ->default('percent')
                            ->live(),

                        Forms\Components\TextInput::make('discount_value')
                            ->label(fn (Forms\Get $get) => $get('discount_type') === 'percent' ? 'Процент (%)' : 'Сума (€)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->placeholder(fn (Forms\Get $get) => $get('discount_type') === 'percent' ? '10' : '50'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Валидност и ограничения')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Валиден от')
                            ->nullable(),

                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Валиден до')
                            ->nullable(),

                        Forms\Components\TextInput::make('max_uses')
                            ->label('Максимален брой ползвания')
                            ->numeric()
                            ->nullable()
                            ->placeholder('Оставете празно = неограничен'),

                        Forms\Components\TextInput::make('uses_count')
                            ->label('Текущи ползвания')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('discount_label')
                    ->label('Отстъпка')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('source')
                    ->label('Канал')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Ползвания')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_uses')
                    ->label('Макс.')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ?? '∞'),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Валиден до')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активен'),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Поръчки')
                    ->counts('orders')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Канал')
                    ->options([
                        'Instagram'     => 'Instagram',
                        'Facebook'      => 'Facebook',
                        'Email'         => 'Email кампания',
                        'Flyer'         => 'Флаер / Принт',
                        'Partner'       => 'Партньор',
                        'Website'       => 'Уебсайт',
                        'Word of Mouth' => 'Препоръка',
                        'Other'         => 'Друго',
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit'   => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}

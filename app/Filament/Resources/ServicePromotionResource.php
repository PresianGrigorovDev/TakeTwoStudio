<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicePromotionResource\Pages;
use App\Models\ServicePromotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicePromotionResource extends Resource
{
    protected static ?string $model = ServicePromotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $modelLabel = 'Промоция на услуга';
    protected static ?string $pluralModelLabel = 'Промоции на услуги';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детайли на промоцията')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name_bg')
                            ->label('Услуга')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('service_package_id', null)),
                        Forms\Components\Select::make('service_package_id')
                            ->label('Пакет / Цена')
                            ->options(function ($get) {
                                $serviceId = $get('service_id');
                                if (!$serviceId) return [];
                                return \App\Models\ServicePackage::where('service_id', $serviceId)
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [$p->id => $p->name_bg . ' — ' . number_format($p->price_eur, 2) . ' €']);
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $pkg = \App\Models\ServicePackage::find($state);
                                    $set('original_price', $pkg?->price_eur);
                                }
                            })
                            ->placeholder('Първо избери услуга'),
                        Forms\Components\TextInput::make('name')
                            ->label('Име на промоцията')
                            ->placeholder('напр. Пролетна Промоция')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Radio::make('discount_type')
                            ->label('Вид намаление')
                            ->options([
                                'percent' => 'Процент (%)',
                                'fixed'   => 'Фиксирана цена (€)',
                            ])
                            ->default('percent')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('original_price')
                            ->label('Оригинална цена (€)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('€')
                            ->live()
                            ->readOnly()
                            ->helperText('Автоматично от избрания пакет')
                            ->visible(fn ($get) => $get('discount_type') === 'percent')
                            ->required(fn ($get) => $get('discount_type') === 'percent'),
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Намаление (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('%')
                            ->live()
                            ->visible(fn ($get) => $get('discount_type') === 'percent')
                            ->required(fn ($get) => $get('discount_type') === 'percent'),
                        Forms\Components\Placeholder::make('discounted_price_preview')
                            ->label('Нова цена след намалението')
                            ->content(function ($get) {
                                $original = (float) $get('original_price');
                                $percent  = (float) $get('discount_percent');
                                if ($original <= 0 || $percent <= 0) {
                                    return '—';
                                }
                                $discounted = $original - ($original * $percent / 100);
                                return number_format($discounted, 2, '.', ' ') . ' €';
                            })
                            ->visible(fn ($get) => $get('discount_type') === 'percent'),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Намалена цена (€)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('€')
                            ->visible(fn ($get) => $get('discount_type') === 'fixed')
                            ->required(fn ($get) => $get('discount_type') === 'fixed'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Времеви период (Законови ограничения)')
                    ->description('Максимална продължителност 30 дни. Изисква се 30 дни пауза преди следваща промоция.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Начало на промоцията')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->rules([
                                fn ($get, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $serviceId = $get('service_id');
                                    if (!$serviceId || !$value) return;

                                    $proposedStart = \Carbon\Carbon::parse($value);
                                    
                                    $query = \App\Models\ServicePromotion::where('service_id', $serviceId)
                                        ->where('is_active', true);
                                        
                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }
                                    
                                    $otherPromotions = $query->get();
                                    
                                    foreach ($otherPromotions as $promo) {
                                        $promoStart = \Carbon\Carbon::parse($promo->starts_at);
                                        $promoEnd = \Carbon\Carbon::parse($promo->expires_at);
                                        
                                        // Overlap check
                                        if ($proposedStart->between($promoStart, $promoEnd)) {
                                            $fail("Избраната начална дата съвпада с друга активна промоция (" . $promoStart->format('d.m.Y H:i') . " - " . $promoEnd->format('d.m.Y H:i') . ").");
                                            return;
                                        }
                                        
                                        // Cooldown check (past promotions relative to proposed start)
                                        if ($promoEnd->isBefore($proposedStart)) {
                                            $hoursDiff = $promoEnd->diffInHours($proposedStart);
                                            if ($hoursDiff < 30 * 24) {
                                                $daysDiff = round($hoursDiff / 24, 1);
                                                $fail("По закон трябва да има поне 30 дни пауза между две промоции за една и съща услуга. Предишната промоция приключва на " . $promoEnd->format('d.m.Y H:i') . " (изминали са само " . $daysDiff . " дни).");
                                                return;
                                            }
                                        }
                                    }
                                }
                            ]),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Край на промоцията')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->after('starts_at')
                            ->rules([
                                fn ($get, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $serviceId = $get('service_id');
                                    $startsAt = $get('starts_at');
                                    if (!$serviceId || !$value || !$startsAt) return;

                                    $proposedStart = \Carbon\Carbon::parse($startsAt);
                                    $proposedEnd = \Carbon\Carbon::parse($value);
                                    
                                    // 1. Duration check
                                    $hoursDuration = $proposedStart->diffInHours($proposedEnd);
                                    if ($hoursDuration > 30 * 24) {
                                        $fail('Продължителността на една промоция не може да бъде повече от 30 дни по закон.');
                                        return;
                                    }
                                    
                                    $query = \App\Models\ServicePromotion::where('service_id', $serviceId)
                                        ->where('is_active', true);
                                        
                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }
                                    
                                    $otherPromotions = $query->get();
                                    
                                    foreach ($otherPromotions as $promo) {
                                        $promoStart = \Carbon\Carbon::parse($promo->starts_at);
                                        $promoEnd = \Carbon\Carbon::parse($promo->expires_at);
                                        
                                        // Overlap check
                                        if ($proposedEnd->between($promoStart, $promoEnd)) {
                                            $fail("Избраната крайна дата съвпада с друга активна промоция (" . $promoStart->format('d.m.Y H:i') . " - " . $promoEnd->format('d.m.Y H:i') . ").");
                                            return;
                                        }
                                        
                                        // Proposed fully wraps another
                                        if ($proposedStart->isBefore($promoStart) && $proposedEnd->isAfter($promoEnd)) {
                                            $fail("Новата промоция не може да покрива съществуваща промоция (" . $promoStart->format('d.m.Y H:i') . " - " . $promoEnd->format('d.m.Y H:i') . ").");
                                            return;
                                        }
                                        
                                        // Cooldown check (future promotions relative to proposed end)
                                        if ($promoStart->isAfter($proposedEnd)) {
                                            $hoursDiff = $proposedEnd->diffInHours($promoStart);
                                            if ($hoursDiff < 30 * 24) {
                                                $daysDiff = round($hoursDiff / 24, 1);
                                                $fail("По закон трябва да има поне 30 дни пауза между две промоции за една и съща услуга. Следващата промоция започва на " . $promoStart->format('d.m.Y H:i') . " (паузата е само " . $daysDiff . " дни, нужни са 30).");
                                                return;
                                            }
                                        }
                                    }
                                }
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name_bg')
                    ->label('Услуга')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Промоция')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('Намаление')
                    ->formatStateUsing(fn ($record) => $record->discount_type === 'percent'
                        ? ($record->discount_percent . '%')
                        : number_format((float)$record->discount_amount, 2, '.', ' ') . ' €')
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Край')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service')
                    ->relationship('service', 'name_bg')
                    ->label('Филтър по Услуга'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Само активни'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicePromotions::route('/'),
            'create' => Pages\CreateServicePromotion::route('/create'),
            'edit' => Pages\EditServicePromotion::route('/{record}/edit'),
        ];
    }
}

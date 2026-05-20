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
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label('Име на промоцията')
                            ->placeholder('напр. Пролетна Промоция')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Намаление (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('%')
                            ->required(),
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
                    ->label('Процент')
                    ->suffix('%')
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
                Tables\Filters\ToggledFilter::make('is_active')
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

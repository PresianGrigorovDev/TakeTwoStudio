<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $navigationGroup = 'Запитвания';
    protected static ?string $navigationLabel = 'Поръчки';
    protected static ?string $pluralModelLabel = 'Поръчки';
    protected static ?string $modelLabel = 'Поръчка';

    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'new')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Име на клиента')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->label('Имейл')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Select::make('service_type')
                    ->label('Тип услуга')
                    ->placeholder('Изберете услуга')
                    ->options([
                        'Сватба' => 'Сватба',
                        'Абитуриентски Бал' => 'Абитуриентски Бал',
                        'Свето Кръщение' => 'Свето Кръщение',
                        'Общо запитване' => 'Общо запитване',
                        'Поръчка от калкулатор' => 'Поръчка от калкулатор',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Цена (€)')
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Нова',
                        'contacted' => 'Свързали сме се',
                        'completed' => 'Завършена',
                        'cancelled' => 'Отказана',
                    ])
                    ->required()
                    ->default('new'),
                Forms\Components\Textarea::make('details')
                    ->label('Детайли на поръчката')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Клиент')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Услуга')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена (€)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new' => 'Нова',
                        'contacted' => 'Свързали сме се',
                        'completed' => 'Завършена',
                        'cancelled' => 'Отказана',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Получена на')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновена на')
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Filament\Resources\InquiryResource\RelationManagers;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InquiryResource extends Resource
{
    protected static ?string $navigationGroup = 'Запитвания';
    protected static ?string $navigationLabel = 'Запитвания';
    protected static ?string $pluralModelLabel = 'Запитвания';
    protected static ?string $modelLabel = 'Запитване';

    protected static ?string $model = Inquiry::class;

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
                Forms\Components\TextInput::make('customer_name')
                    ->label('Име на клиента')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('customer_email')
                    ->label('Имейл')
                    ->email()
                    ->maxLength(100),
                Forms\Components\TextInput::make('customer_phone')
                    ->label('Телефон')
                    ->required()
                    ->maxLength(30),
                Forms\Components\TextInput::make('service_type')
                    ->label('Тип услуга')
                    ->maxLength(50),
                Forms\Components\DatePicker::make('event_date')
                    ->label('Дата на събитието'),
                Forms\Components\TextInput::make('school_class')
                    ->label('Училище / Клас')
                    ->maxLength(100),
                Forms\Components\TextInput::make('final_price_eur')
                    ->label('Крайна цена (€)')
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\Textarea::make('message')
                    ->label('Дoпълнително съобщение')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('selected_details')
                    ->label('Избрани детайли от калкулатора')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Ново',
                        'contacted' => 'Свързали сме се',
                        'booked' => 'Резервирано',
                        'completed' => 'Завършено',
                        'cancelled' => 'Отказано',
                    ])
                    ->required()
                    ->default('new'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Услуга')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_price_eur')
                    ->label('Цена (€)')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new' => 'Ново',
                        'contacted' => 'Свързали сме се',
                        'booked' => 'Резервирано',
                        'completed' => 'Завършено',
                        'cancelled' => 'Отказано',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'booked' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Получено на')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновено на')
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
            'index' => Pages\ListInquiries::route('/'),
            'create' => Pages\CreateInquiry::route('/create'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}

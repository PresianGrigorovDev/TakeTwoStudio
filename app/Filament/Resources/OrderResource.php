<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Booking;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                        'Изпращане' => 'Изпращане',
                        'Реклама и Бизнес' => 'Реклама и Бизнес',
                        'Общо запитване' => 'Общо запитване',
                        'Поръчка от калкулатор' => 'Поръчка от калкулатор',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('event_date')
                    ->label('Дата на събитието')
                    ->live(),
                Forms\Components\Select::make('start_time')
                    ->label('Начален час')
                    ->options(fn () => array_combine(
                        \App\Http\Controllers\BookingController::getWorkingHours(),
                        \App\Http\Controllers\BookingController::getWorkingHours()
                    ))
                    ->live(),
                Forms\Components\Select::make('end_time')
                    ->label('Краен час')
                    ->options(function () {
                        $hours = [];
                        for ($h = \App\Http\Controllers\BookingController::getWorkStart() + 1; $h <= \App\Http\Controllers\BookingController::getWorkEnd(); $h++) {
                            $val = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                            $hours[$val] = $val;
                        }
                        return $hours;
                    })
                    ->live(),
                Forms\Components\TextInput::make('price')
                    ->label('Цена (€)')
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Нова',
                        'contacted' => 'Свързали сме се',
                        'accepted' => 'Приета',
                        'completed' => 'Завършена',
                        'cancelled' => 'Отказана',
                    ])
                    ->required()
                    ->default('new'),
                Forms\Components\Select::make('teamMembers')
                    ->label('Назначени фотографи')
                    ->multiple()
                    ->relationship('teamMembers', 'name')
                    ->searchable()
                    ->preload()
                    ->options(function (Forms\Get $get, ?Order $record) {
                        $eventDate = $get('event_date');
                        $startTime = $get('start_time');
                        $endTime = $get('end_time');

                        $allMembers = \App\Models\TeamMember::pluck('name', 'id');

                        if (!$eventDate || !$startTime || !$endTime) {
                            return $allMembers;
                        }

                        $busyIds = self::getBusyMemberIds($eventDate, $startTime, $endTime, $record);

                        return $allMembers->mapWithKeys(function ($name, $id) use ($busyIds) {
                            if ($busyIds->contains($id)) {
                                return [$id => $name . ' (зает)'];
                            }
                            return [$id => $name];
                        });
                    })
                    ->disableOptionWhen(function (string $value, Forms\Get $get, ?Order $record) {
                        $eventDate = $get('event_date');
                        $startTime = $get('start_time');
                        $endTime = $get('end_time');

                        if (!$eventDate || !$startTime || !$endTime) {
                            return false;
                        }

                        return self::getBusyMemberIds($eventDate, $startTime, $endTime, $record)
                            ->contains((int) $value);
                    }),
                Forms\Components\Textarea::make('details')
                    ->label('Детайли на поръчката')
                    ->columnSpanFull(),
            ]);
    }

    private static function getBusyMemberIds(string $eventDate, string $startTime, string $endTime, ?Order $record): \Illuminate\Support\Collection
    {
        $busyFromBookings = Booking::where('event_date', $eventDate)
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereNotNull('team_member_id')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($record, fn ($q) => $q->where('order_id', '!=', $record->id))
            ->pluck('team_member_id');

        $busyFromOrders = Order::where('event_date', $eventDate)
            ->whereNotIn('status', ['cancelled'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->whereDoesntHave('booking')
            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
            ->whereHas('teamMembers')
            ->with('teamMembers')
            ->get()
            ->flatMap(fn ($order) => $order->teamMembers->pluck('id'));

        return $busyFromBookings->merge($busyFromOrders)->unique();
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
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Услуга')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Час')
                    ->formatStateUsing(fn ($state, $record) => $record->start_time && $record->end_time
                        ? $record->start_time . ' – ' . $record->end_time
                        : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teamMembers.name')
                    ->label('Фотографи')
                    ->badge()
                    ->color('info')
                    ->default('-'),
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
                        'accepted' => 'Приета',
                        'completed' => 'Завършена',
                        'cancelled' => 'Отказана',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'accepted' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Получена на')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Нова',
                        'contacted' => 'Свързали сме се',
                        'accepted' => 'Приета',
                        'completed' => 'Завършена',
                        'cancelled' => 'Отказана',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm_booking')
                    ->label('Запиши в календара')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Запиши в календара')
                    ->modalDescription('Ще бъде създадена резервация в календара с данните от поръчката.')
                    ->visible(fn (Order $record) => $record->event_date
                        && $record->start_time
                        && $record->end_time
                        && !$record->booking()->exists())
                    ->action(function (Order $record) {
                        $teamMemberIds = $record->teamMembers()->pluck('team_members.id');

                        if ($teamMemberIds->isEmpty()) {
                            // Create one booking without a team member
                            Booking::create([
                                'name' => $record->name,
                                'phone' => $record->phone,
                                'email' => $record->email,
                                'event_date' => $record->event_date,
                                'start_time' => $record->start_time,
                                'end_time' => $record->end_time,
                                'service_type' => $record->service_type,
                                'message' => $record->details,
                                'status' => 'confirmed',
                                'order_id' => $record->id,
                            ]);
                        } else {
                            // Create a booking for each assigned team member
                            foreach ($teamMemberIds as $memberId) {
                                Booking::create([
                                    'name' => $record->name,
                                    'phone' => $record->phone,
                                    'email' => $record->email,
                                    'event_date' => $record->event_date,
                                    'start_time' => $record->start_time,
                                    'end_time' => $record->end_time,
                                    'service_type' => $record->service_type,
                                    'message' => $record->details,
                                    'status' => 'confirmed',
                                    'order_id' => $record->id,
                                    'team_member_id' => $memberId,
                                ]);
                            }
                        }

                        $record->update(['status' => 'accepted']);

                        Notification::make()
                            ->title('Резервацията е записана в календара')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

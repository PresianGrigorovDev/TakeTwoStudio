<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Support\OrderPaymentAction;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderShoot;
use App\Support\OrderCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Работа';
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
                    ->required(fn (?Order $record) => $record === null)
                    ->dehydrateStateUsing(fn (?string $state) => $state ?? '')
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->label('Имейл')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Select::make('service_type')
                    ->label('Тип услуга')
                    ->placeholder('Изберете услуга')
                    ->options(fn (Forms\Get $get) => [
                        'Сватба' => 'Сватба',
                        'Абитуриентски Бал' => 'Абитуриентски Бал',
                        'Свето Кръщение' => 'Свето Кръщение',
                        'Изпращане' => 'Изпращане',
                        'Реклама и Бизнес' => 'Реклама и Бизнес',
                        'Общо запитване' => 'Общо запитване',
                        'Поръчка от калкулатор' => 'Поръчка от калкулатор',
                    ] + (is_string($get('service_type')) ? [$get('service_type') => $get('service_type')] : []))
                    ->required(),
                Forms\Components\DatePicker::make('event_date')
                    ->label('Дата на основния ангажимент')
                    ->requiredWith(['start_time', 'end_time'])
                    ->live(),
                ...BookingResource::timeFields(),
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
                    ->label('Екип за основния ангажимент')
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
        $bookingId = $record?->booking?->id;
        ['start_time' => $startTime, 'end_time' => $endTime] = BookingResource::normalizeTimes([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $busyFromCalendar = Booking::active()
            ->overlappingDays($eventDate, $eventDate)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($bookingId, fn ($q) => $q->whereKeyNot($bookingId))
            ->with('teamMembers')
            ->get()
            ->flatMap(fn (Booking $booking) => $booking->teamMembers->modelKeys());

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

        return $busyFromCalendar->merge($busyFromOrders)->unique();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('booking')->withCount([
                'shoots',
                'shoots as remaining_sessions_count' => fn (Builder $shoots) => $shoots->remaining()->where('kind', OrderShoot::SESSION),
                'shoots as remaining_events_count' => fn (Builder $shoots) => $shoots->remaining()->where('kind', OrderShoot::EVENT),
            ]))
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
                Tables\Columns\TextColumn::make('remaining_shoots')
                    ->label('Оставащи заснемания')
                    ->state(function (Order $record): ?string {
                        if (! $record->shoots_count) {
                            return null;
                        }

                        $sessions = (int) $record->remaining_sessions_count;
                        $events = (int) $record->remaining_events_count;

                        return $sessions || $events
                            ? $sessions.($sessions === 1 ? ' фотосесия' : ' фотосесии')
                                .' · '.$events.($events === 1 ? ' събитие' : ' събития')
                            : 'Всичко е заснето';
                    })
                    ->url(fn (Order $record) => self::getUrl('shoots', ['record' => $record]))
                    ->badge()
                    ->color(fn (Order $record) => $record->remaining_sessions_count || $record->remaining_events_count ? 'info' : 'success')
                    ->toggleable(),
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
                Tables\Filters\Filter::make('remaining_shoots')
                    ->label('С оставащи заснемания')
                    ->query(fn (Builder $query) => $query->whereHas('shoots', fn (Builder $shoots) => $shoots->remaining())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('calendar')->label('Ангажимент')->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->visible(fn (Order $record) => self::bookingUrl($record) !== null)
                    ->url(self::bookingUrl(...)),
                OrderPaymentAction::onOrder(Tables\Actions\Action::make('recordPayment')),
                Tables\Actions\Action::make('confirm_booking')
                    ->label('Приеми поръчката')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Приеми поръчката')
                    ->databaseTransaction()
                    ->visible(fn (Order $record) => $record->event_date
                        && in_array($record->status, ['new', 'contacted'], true))
                    ->successNotificationTitle('Поръчката е приета')
                    ->action(function (Order $record, Tables\Actions\Action $action) {
                        $record = Order::query()->lockForUpdate()->findOrFail($record->id);
                        if (! in_array($record->status, ['new', 'contacted'], true)) {
                            return;
                        }
                        $record->update(['status' => 'accepted']);
                        if ($booking = OrderCalendar::fromOrder($record)) {
                            BookingResource::notifyConflicts($booking);
                        }

                        $action->success();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function bookingUrl(Order $order): ?string
    {
        $booking = $order->booking;

        return $booking && BookingResource::canEdit($booking)
            ? BookingResource::getUrl('edit', ['record' => $booking, 'from' => 'order'])
            : null;
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
            'shoots' => Pages\ManageOrderShoots::route('/{record}/shoots'),
        ];
    }
}

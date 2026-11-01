<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Support\TeamCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Entries of the internal team calendar (dashboard): client engagements (incl. the
 * website booking requests), personal busy time and studio-closed days.
 */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Работа';
    protected static ?string $navigationLabel = 'Ангажименти';
    protected static ?string $modelLabel = 'Ангажимент';
    protected static ?string $pluralModelLabel = 'Ангажименти';
    protected static ?int $navigationSort = 1;

    public const SERVICE_TYPES = [
        'Сватба' => 'Сватба',
        'Абитуриентски Бал' => 'Абитуриентски Бал',
        'Свето Кръщене' => 'Свето Кръщене',
        'Изпращане' => 'Изпращане',
        'Реклама и Бизнес' => 'Реклама и Бизнес',
    ];

    public const STATUSES = [
        'pending' => 'Чакаща',
        'confirmed' => 'Потвърдена',
        'rejected' => 'Отказана',
        'cancelled' => 'Отменена',
    ];

    public static function form(Form $form): Form
    {
        $isClient = fn (Get $get) => Booking::isClientKind($get('kind'));

        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\ToggleButtons::make('kind')
                        ->label('Тип')
                        ->options(Booking::KINDS)
                        ->colors([Booking::KIND_CLIENT => 'primary', Booking::KIND_SESSION => 'info', Booking::KIND_BUSY => 'gray', Booking::KIND_CLOSED => 'danger'])
                        ->icons([Booking::KIND_CLIENT => 'heroicon-o-briefcase', Booking::KIND_SESSION => 'heroicon-o-camera', Booking::KIND_BUSY => 'heroicon-o-user-minus', Booking::KIND_CLOSED => 'heroicon-o-no-symbol'])
                        ->default(fn () => in_array(request()->query('kind'), array_keys(Booking::KINDS), true) ? request()->query('kind') : Booking::KIND_CLIENT)
                        ->inline()
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('name')
                        ->label(fn (Get $get) => $isClient($get) ? 'Клиент / заглавие' : 'Причина')
                        ->placeholder(fn (Get $get) => match ($get('kind')) {
                            Booking::KIND_SESSION => 'Абитуриентска фотосесия – Мария',
                            Booking::KIND_BUSY, Booking::KIND_CLOSED => 'Отпуск',
                            default => 'Сватба – Мария и Иван',
                        })
                        ->required()
                        ->maxLength(255),
                    Forms\Components\CheckboxList::make('teamMembers')
                        ->label('Хора')
                        ->relationship('teamMembers', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('display_order'))
                        ->default(fn () => request()->integer('member') ? [request()->integer('member')] : [])
                        ->helperText(fn (Get $get) => match ($get('kind')) {
                            Booking::KIND_CLOSED => 'Студиото е затворено за всички; хора не са нужни.',
                            Booking::KIND_BUSY => 'Кой е зает.',
                            default => 'Може повече от един човек. Ангажимент без хора заема едно място, докато не го разпределите.',
                        })
                        ->required(fn (Get $get) => $get('kind') === Booking::KIND_BUSY)
                        ->hidden(fn (Get $get) => $get('kind') === Booking::KIND_CLOSED)
                        ->live()
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Кога')
                ->schema([
                    Forms\Components\DatePicker::make('event_date')
                        ->label('Дата')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->default(fn () => request()->query('date'))
                        ->required()
                        ->live(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('До дата (по избор)')
                        ->helperText('За няколко поредни дни, напр. отпуск. Часовете важат за всеки ден.')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->afterOrEqual('event_date')
                        ->live(),
                    Forms\Components\TimePicker::make('start_time')
                        ->label('От')
                        ->seconds(false)
                        ->default(fn () => preg_match('/^\d{2}:\d{2}$/', (string) request()->query('start')) ? request()->query('start') : sprintf('%02d:00', BookingController::getWorkStart()))
                        ->required()
                        ->live(onBlur: true),
                    Forms\Components\TimePicker::make('end_time')
                        ->label('До')
                        ->seconds(false)
                        ->default(fn () => preg_match('/^\d{2}:\d{2}$/', (string) request()->query('end')) ? request()->query('end') : sprintf('%02d:00', BookingController::getWorkEnd()))
                        ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                            // "HH:MM" strings compare correctly as text.
                            if ($get('start_time') && substr((string) $value, 0, 5) <= substr((string) $get('start_time'), 0, 5)) {
                                $fail('Крайният час трябва да е след началния.');
                            }
                        })
                        ->required()
                        ->live(onBlur: true),
                    Forms\Components\TextInput::make('location')
                        ->label('Локация')
                        ->placeholder('Ресторант „Морско конче“, Варна')
                        ->helperText('Къде трябва да отидем – вижда се в календара и води към Google Maps.')
                        ->maxLength(255)
                        ->hidden(fn (Get $get) => $get('kind') === Booking::KIND_CLOSED)
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('conflicts')
                        ->hiddenLabel()
                        ->content(fn (Get $get, ?Booking $record) => self::conflictWarning($get, $record))
                        ->visible(fn (Get $get, ?Booking $record) => self::conflictWarning($get, $record) !== null)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Клиент')
                ->schema([
                    Forms\Components\Select::make('service_type')
                        ->label('Тип услуга')
                        ->options(self::SERVICE_TYPES),
                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options(self::STATUSES)
                        ->required()
                        ->default('confirmed'),
                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('email')
                        ->label('Имейл')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\Select::make('order_id')
                        ->label('Свързана поръчка')
                        ->relationship('order', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('message')
                        ->label('Съобщение от клиента')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible($isClient),

            Forms\Components\Textarea::make('admin_notes')
                ->label('Вътрешни бележки')
                ->columnSpanFull(),
        ]);
    }

    /**
     * Internal entries (busy / closed) have no client data and are always confirmed;
     * a closed studio has no people. Called by the create and edit pages.
     */
    public static function normalizeData(array $data): array
    {
        if (! Booking::isClientKind($data['kind'] ?? null)) {
            $data['status'] = 'confirmed';
            foreach (['service_type', 'phone', 'email', 'message', 'order_id'] as $field) {
                $data[$field] = null;
            }
        }
        foreach (['start_time', 'end_time'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = substr((string) $data[$field], 0, 5);
            }
        }
        if (! empty($data['end_date']) && substr((string) $data['end_date'], 0, 10) <= substr((string) $data['event_date'], 0, 10)) {
            $data['end_date'] = null;
        }

        return $data;
    }

    /** Notification after saving when somebody is double-booked (saving is still allowed). */
    public static function notifyConflicts(Booking $record): void
    {
        if ($record->kind === Booking::KIND_CLOSED) {
            $record->teamMembers()->detach();

            return;
        }

        $memberIds = $record->teamMembers()->pluck('team_members.id')->all();
        $lines = TeamCalendar::describeConflicts(
            TeamCalendar::conflicts($record->event_date->toDateString(), $record->end_date?->toDateString(), $record->start_time, $record->end_time, $memberIds, $record->id),
            $memberIds
        );

        if ($lines) {
            \Filament\Notifications\Notification::make()
                ->title('Внимание: двойна заетост')
                ->body(implode("\n", $lines))
                ->warning()
                ->persistent()
                ->send();
        }
    }

    /**
     * "Добави в календар" menu: Google Calendar (prefilled event) and Apple / Outlook (.ics).
     * Built for both table and page actions (same API, different classes).
     *
     * @param class-string $group
     * @param class-string $action
     */
    public static function addToCalendarActions(string $group, string $action)
    {
        return $group::make([
            $action::make('google_calendar')
                ->label('Google Calendar')
                ->icon('heroicon-o-calendar')
                ->url(fn (Booking $record) => \App\Support\CalendarExport::googleUrl($record), shouldOpenInNewTab: true),
            $action::make('apple_calendar')
                ->label('Apple / iPhone / Outlook (.ics)')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Booking $record) => route('admin.booking.ics', $record)),
        ])
            ->label('Добави в календар')
            ->icon('heroicon-o-calendar-days')
            ->tooltip('Добави в Google или Apple календар');
    }

    /** Google Maps search link for a free-text location (null when empty). */
    public static function mapsUrl(?string $location): ?string
    {
        return filled($location) ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($location) : null;
    }

    /** Live warning when a chosen person (or the closed studio) is already taken. */
    public static function conflictWarning(Get $get, ?Booking $record): ?HtmlString
    {
        $date = $get('event_date');
        // Time pickers may hold "13:30:00"; entries are stored as "HH:MM".
        $start = $get('start_time') ? substr((string) $get('start_time'), 0, 5) : null;
        $end = $get('end_time') ? substr((string) $get('end_time'), 0, 5) : null;

        if (! $date || ! $start || ! $end || $start >= $end || $get('kind') === Booking::KIND_CLOSED) {
            return null;
        }

        $memberIds = (array) $get('teamMembers');
        $lines = TeamCalendar::describeConflicts(
            TeamCalendar::conflicts(substr((string) $date, 0, 10), $get('end_date') ? substr((string) $get('end_date'), 0, 10) : null, $start, $end, $memberIds, $record?->id),
            $memberIds
        );

        if (! $lines) {
            return null;
        }

        return new HtmlString(
            '<div class="tt-conflict-warning"><strong>Внимание: двойна заетост</strong><ul>'
            .collect($lines)->map(fn ($l) => '<li>'.e($l).'</li>')->implode('')
            .'</ul><span>Можете да запишете въпреки това.</span></div>'
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Дата')
                    ->formatStateUsing(fn (Booking $record) => $record->event_date->format('d.m.Y')
                        .($record->end_date && $record->end_date->gt($record->event_date) ? ' – '.$record->end_date->format('d.m.Y') : ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Час')
                    ->formatStateUsing(fn ($state, Booking $record) => $record->start_time.' – '.$record->end_time),
                Tables\Columns\TextColumn::make('kind')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Booking::KINDS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Booking::KIND_SESSION => 'info',
                        Booking::KIND_BUSY => 'gray',
                        Booking::KIND_CLOSED => 'danger',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Клиент / заглавие')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Локация')
                    ->icon('heroicon-o-map-pin')
                    ->url(fn (Booking $record) => self::mapsUrl($record->location), shouldOpenInNewTab: true)
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('teamMembers.name')
                    ->label('Хора')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Услуга')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Създадена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->label('Само предстоящи')
                    ->query(fn (Builder $query) => $query->where(fn ($q) => $q->whereDate('event_date', '>=', today())->orWhereDate('end_date', '>=', today())))
                    ->default(),
                Tables\Filters\SelectFilter::make('kind')
                    ->label('Тип')
                    ->options(Booking::KINDS),
                Tables\Filters\SelectFilter::make('teamMembers')
                    ->label('Човек')
                    ->relationship('teamMembers', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(self::STATUSES),
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Услуга')
                    ->options(self::SERVICE_TYPES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::addToCalendarActions(Tables\Actions\ActionGroup::class, Tables\Actions\Action::class),
                Tables\Actions\Action::make('confirm')
                    ->label('Потвърди')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Потвърждаване на резервация')
                    ->modalDescription('Сигурни ли сте, че искате да потвърдите тази резервация? Ще бъде изпратен имейл до клиента.')
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'confirmed']);
                        if ($record->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($record->email)
                                    ->send(new \App\Mail\BookingConfirmedNotification($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send booking confirmation: " . $e->getMessage());
                            }
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Откажи')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Отказване на резервация')
                    ->modalDescription('Сигурни ли сте, че искате да откажете тази резервация? Ще бъде изпратен имейл до клиента.')
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'rejected']);
                        if ($record->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($record->email)
                                    ->send(new \App\Mail\BookingRejectedNotification($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send booking rejection: " . $e->getMessage());
                            }
                        }
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}

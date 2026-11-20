<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Support\PackageShootPlan;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderShoot;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ManageOrderShoots extends ManageRelatedRecords
{
    protected static string $resource = OrderResource::class;

    protected static string $relationship = 'shoots';

    protected static ?string $breadcrumb = 'Заснемания';

    public static function canAccess(array $parameters = []): bool
    {
        $order = $parameters['record'] ?? null;

        return $order instanceof Order && OrderResource::canEdit($order) && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return 'Заснемания · '.$this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('order')->label('Към поръчката')->icon('heroicon-o-arrow-left')->color('gray')
                ->url(fn () => OrderResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema(PackageShootPlan::fields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('заснемане')
            ->pluralModelLabel('заснемания')
            ->recordTitleAttribute('name')
            ->recordAction(null)
            ->heading(fn () => $this->getRecord()->shoot_package_name)
            ->description($this->summary(...))
            ->emptyStateHeading('Няма добавени заснемания')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['booking', 'shotBy']))
            ->defaultSort('id')
            ->columns([
                Split::make([
                    TextColumn::make('name')->weight('medium')->wrap()
                        ->description(fn (OrderShoot $record) => OrderShoot::KINDS[$record->kind]),
                    TextColumn::make('progress')->badge()->grow(false)
                        ->state($this->progress(...))
                        ->color(fn (string $state) => match ($state) {
                            'Заснето' => 'success',
                            'Насрочено' => 'info',
                            'Чака потвърждение', 'За отбелязване', 'За пренасрочване' => 'warning',
                            default => 'gray',
                        }),
                    TextColumn::make('shoot_date')->grow(false)
                        ->state($this->displayDate(...))
                        ->placeholder('Без дата')
                        ->description($this->dateDetails(...)),
                ])->from('md'),
            ])
            ->headerActions([
                $this->applyPackageAction(),
                Actions\CreateAction::make()->label('Добави заснемане')->color('gray')
                    ->createAnother(false)->databaseTransaction()
                    ->before($this->lockOrder(...)),
            ])
            ->actions([
                Actions\Action::make('schedule')->label('Насрочи')->icon('heroicon-o-calendar-days')
                    ->visible(fn (OrderShoot $record) => $record->canSchedule() && BookingResource::canCreate())
                    ->url(fn (OrderShoot $record) => BookingResource::getUrl('create', ['shoot' => $record->id])),
                $this->markShotAction(),
                Actions\ActionGroup::make([
                    $this->connectBookingAction(),
                    Actions\Action::make('openBooking')->label('Отвори ангажимента')->icon('heroicon-o-calendar')
                        ->visible(fn (OrderShoot $record) => $record->booking && BookingResource::canEdit($record->booking))
                        ->url(fn (OrderShoot $record) => BookingResource::getUrl('edit', [
                            'record' => $record->booking,
                            'from' => 'order-shoot',
                        ])),
                    Actions\Action::make('disconnectBooking')->label('Премахни връзката с календара')->icon('heroicon-o-link-slash')
                        ->visible(fn (OrderShoot $record) => $record->booking_id !== null)
                        ->databaseTransaction()
                        ->action(fn (OrderShoot $record) => $this->lockShoot($record)->update(['booking_id' => null])),
                    Actions\Action::make('undoShot')->label('Върни като оставащо')->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (OrderShoot $record) => $record->shot_on !== null)
                        ->databaseTransaction()
                        ->action(fn (OrderShoot $record) => $this->lockShoot($record)->update(['shot_on' => null, 'shot_by_id' => null])),
                    Actions\EditAction::make()->label('Редактирай')->databaseTransaction(),
                    Actions\DeleteAction::make()->label('Премахни')->modalHeading('Премахни заснемането')
                        ->modalDescription(fn (OrderShoot $record) => $record->booking_id
                            ? 'Можете да отмените свързания ангажимент отделно в календара.'
                            : 'Редът ще бъде премахнат от плана на поръчката.')
                        ->databaseTransaction(),
                ])->label('Още действия')->tooltip('Още действия'),
            ]);
    }

    private function applyPackageAction(): Actions\Action
    {
        return Actions\Action::make('applyPackage')->label('Избери пакет')->icon('heroicon-o-rectangle-stack')
            ->visible(fn () => ! $this->getRelationship()->exists())
            ->modalHeading('Заснемания от пакет')
            ->modalSubmitActionLabel('Добави към поръчката')
            ->form([
                Select::make('package')->label('Пакет')->options(PackageShootPlan::options(...))
                    ->searchable()->required()->rule('string')->live()
                    ->helperText('Тук се показват пакетите със зададени заснемания в „Услуги“.')
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $set('shoot_plan', $state ? PackageShootPlan::find($state)?->shoot_plan ?? [] : []);
                    }),
                PackageShootPlan::items()->label('Включени заснемания')->minItems(1)
                    ->helperText('Можете да добавите, премахнете или преименувате редове за тази поръчка.')
                    ->visible(fn (Get $get) => filled($get('package'))),
            ])
            ->databaseTransaction()
            ->successNotificationTitle('Пакетът е добавен към поръчката')
            ->action(function (array $data, Actions\Action $action): void {
                $order = $this->lockOrder();
                $package = PackageShootPlan::find($data['package']);

                if (! $package || empty($package->shoot_plan)) {
                    $this->actionError('package', 'Този пакет вече не съдържа заснемания. Изберете друг.');
                }
                if ($order->shoots()->exists()) {
                    $this->actionError('package', 'Към поръчката вече има заснемания. Опреснете страницата, за да ги видите.');
                }

                $order->shoot_package_name = $package->name_bg ?? $package->name;
                $order->save();
                foreach ($data['shoot_plan'] as $item) {
                    $order->shoots()->create(['name' => $item['name'], 'kind' => $item['kind']]);
                }

                $this->getRecord()->refresh();
                $action->success();
            });
    }

    private function markShotAction(): Actions\Action
    {
        return Actions\Action::make('markShot')->label('Заснето')->icon('heroicon-o-check-circle')->color('success')
            ->visible(fn (OrderShoot $record) => $record->shot_on === null)
            ->modalHeading('Отбележи като заснето')
            ->modalSubmitActionLabel('Отбележи заснето')
            ->form([
                DatePicker::make('shot_on')->label('Дата на заснемането')->required()
                    ->default(function (OrderShoot $record): string {
                        $today = now('Europe/Sofia')->toDateString();
                        $scheduled = $record->booking?->isActive() ? $record->booking->event_date->toDateString() : null;

                        return $scheduled && $scheduled <= $today ? $scheduled : $today;
                    })
                    ->maxDate(fn () => now('Europe/Sofia')->toDateString()),
            ])
            ->databaseTransaction()
            ->successNotificationTitle('Заснемането е отбелязано')
            ->action(function (OrderShoot $record, array $data, Actions\Action $action): void {
                $shoot = $this->lockShoot($record);
                if ($shoot->shot_on === null) {
                    $shoot->update(['shot_on' => $data['shot_on'], 'shot_by_id' => auth()->id()]);
                }

                $action->success();
            });
    }

    private function connectBookingAction(): Actions\Action
    {
        return Actions\Action::make('connectBooking')->label('Свържи съществуващ ангажимент')->icon('heroicon-o-link')
            ->visible(fn (OrderShoot $record) => $record->canSchedule() && BookingResource::canViewAny())
            ->modalSubmitActionLabel('Свържи')
            ->form([
                Select::make('booking_id')->label('Ангажимент')->required()->rule('integer')->searchable()
                    ->options(fn () => $this->bookingOptions())
                    ->getSearchResultsUsing($this->bookingOptions(...))
                    ->getOptionLabelUsing(fn ($value) => ($booking = Booking::find($value)) ? $this->bookingLabel($booking) : null)
                    ->helperText('Можете да изберете запис за тази поръчка или запис без свързана поръчка.'),
            ])
            ->databaseTransaction()
            ->successNotificationTitle('Ангажиментът е свързан')
            ->action(function (OrderShoot $record, array $data, Actions\Action $action): void {
                $booking = $this->eligibleBookings()->lockForUpdate()->find($data['booking_id']);
                $shoot = $this->lockShoot($record);

                if (! $booking || ! $shoot->canSchedule() || $booking->orderShoot()->exists()) {
                    $this->actionError('booking_id', 'Ангажиментът вече не е наличен или заснемането е планирано. Опреснете страницата.');
                }

                $shoot->update(['booking_id' => $booking->id]);
                $action->success();
            });
    }

    private function summary(): ?string
    {
        $shoots = $this->getRelationship()->get(['kind', 'shot_on']);
        if ($shoots->isEmpty()) {
            return null;
        }

        $sessions = $shoots->where('kind', OrderShoot::SESSION);
        $events = $shoots->where('kind', OrderShoot::EVENT);

        return 'Фотосесии · '.$sessions->whereNull('shot_on')->count().' от '.$sessions->count().' остават'
            .'  |  Събития · '.$events->whereNull('shot_on')->count().' от '.$events->count().' остават';
    }

    private function progress(OrderShoot $record): string
    {
        $booking = $record->booking;

        return match (true) {
            $record->shot_on !== null => 'Заснето',
            $booking === null => 'За насрочване',
            ! $booking->isActive() => 'За пренасрочване',
            $booking->last_date->toDateString() < now('Europe/Sofia')->toDateString() => 'За отбелязване',
            $booking->status === 'pending' => 'Чака потвърждение',
            default => 'Насрочено',
        };
    }

    private function dateDetails(OrderShoot $record): ?string
    {
        if ($record->shot_on) {
            return $record->shotBy?->name;
        }
        $booking = $record->booking;
        if ($booking?->isActive() && $booking->start_time && $booking->end_time) {
            return substr($booking->start_time, 0, 5).'–'.substr($booking->end_time, 0, 5);
        }

        return null;
    }

    private function displayDate(OrderShoot $record): ?string
    {
        $booking = $record->booking;
        if ($record->shot_on || ! $booking) {
            return $record->shot_on?->format('d.m.Y');
        }

        $start = $booking->event_date->format('d.m.Y');

        return $booking->last_date->gt($booking->event_date) ? $start.' – '.$booking->last_date->format('d.m.Y') : $start;
    }

    private function eligibleBookings(): Builder
    {
        return Booking::active()->whereIn('kind', Booking::CLIENT_KINDS)
            ->where(fn (Builder $query) => $query->whereNull('order_id')->orWhere('order_id', $this->getRecord()->id));
    }

    private function bookingOptions(?string $search = null): array
    {
        return $this->eligibleBookings()->whereDoesntHave('orderShoot')
            ->when(filled($search), fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByRaw('case when order_id = ? then 0 else 1 end', [$this->getRecord()->id])
            ->latest('event_date')->limit(25)->get()
            ->mapWithKeys(fn (Booking $booking) => [$booking->id => $this->bookingLabel($booking)])
            ->all();
    }

    private function bookingLabel(Booking $booking): string
    {
        return $booking->event_date->format('d.m.Y').' · '.$booking->name;
    }

    private function lockOrder(): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($this->getRecord()->id);
    }

    private function lockShoot(OrderShoot $shoot): OrderShoot
    {
        return $this->getRelationship()->lockForUpdate()->findOrFail($shoot->id);
    }

    private function actionError(string $field, string $message): never
    {
        $state = $this->getMountedTableActionForm()->getStatePath();

        throw ValidationException::withMessages(["{$state}.{$field}" => $message]);
    }
}

<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Pages\Concerns\ReturnsToCalendar;
use App\Filament\Resources\OrderResource;
use App\Models\OrderShoot;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;

class CreateBooking extends CreateRecord
{
    use ReturnsToCalendar;

    protected static string $resource = BookingResource::class;

    #[Locked]
    public ?int $orderShootId = null;

    private ?OrderShoot $shootToSchedule = null;

    public function mount(): void
    {
        parent::mount();
        $this->rememberCalendar();

        $shootId = request()->query('shoot');
        if ($shootId === null) {
            return;
        }

        $validated = filter_var($shootId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($validated === false, 404);

        $this->orderShootId = $validated;
        $shoot = $this->resolveShoot();
        $order = $shoot->order;
        $this->calendarReturnUrl = OrderResource::getUrl('shoots', ['record' => $order]);

        if (! $shoot->canSchedule()) {
            $this->returnToPlannedShoots($shoot);

            return;
        }

        $this->form->fill(array_replace($this->data ?? [], [
            'kind' => $shoot->bookingKind(),
            'name' => mb_substr($shoot->name.' – '.$order->name, 0, 255),
            'phone' => $order->phone,
            'email' => $order->email,
            'service_type' => $order->service_type,
            'order_id' => $order->id,
            'teamMembers' => $order->teamMembers()->where('team_members.is_active', true)
                ->pluck('team_members.id')->map(fn ($id) => (string) $id)->all(),
        ]));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->orderShootId !== null) {
            // Filament also saves the booking and its team within this transaction.
            $shoot = $this->resolveShoot(lock: true);
            if (! $shoot->canSchedule()) {
                $this->returnToPlannedShoots($shoot);
                $this->halt(shouldRollbackDatabaseTransaction: true);
            }

            $this->shootToSchedule = $shoot;
            $data['kind'] = $shoot->bookingKind();
            $data['order_id'] = $shoot->order_id;
        }

        return BookingResource::normalizeData($data);
    }

    protected function afterCreate(): void
    {
        $this->shootToSchedule?->update(['booking_id' => $this->record->id]);
        BookingResource::notifyConflicts($this->record);
    }

    public function getTitle(): string | Htmlable
    {
        return $this->orderShootId !== null ? 'Насрочи заснемане' : parent::getTitle();
    }

    protected function getCreateFormAction(): Action
    {
        $action = parent::getCreateFormAction();

        return $this->orderShootId !== null ? $action->label('Запиши ангажимент') : $action;
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->hidden($this->orderShootId !== null);
    }

    protected function getRedirectUrl(): string
    {
        return $this->calendarReturnUrl ?? parent::getRedirectUrl();
    }

    private function resolveShoot(bool $lock = false): OrderShoot
    {
        $query = OrderShoot::query();
        if ($lock) {
            $query->lockForUpdate();
        }

        $shoot = $query->findOrFail($this->orderShootId);
        abort_unless(OrderResource::canEdit($shoot->order), 403);

        return $shoot;
    }

    private function returnToPlannedShoots(OrderShoot $shoot): void
    {
        Notification::make()->warning()
            ->title($shoot->shot_on ? 'Заснемането вече е отбелязано като заснето' : 'Заснемането вече има ангажимент')
            ->send();

        $this->redirect($this->calendarReturnUrl);
    }
}

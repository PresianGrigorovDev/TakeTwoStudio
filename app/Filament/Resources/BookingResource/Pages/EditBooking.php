<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookingResource::addToCalendarActions(Actions\ActionGroup::class, Actions\Action::class)
                ->button()
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return BookingResource::normalizeData($data);
    }

    protected function afterSave(): void
    {
        BookingResource::notifyConflicts($this->record);
    }

    /** Opened from a calendar ('calendar' = dashboard, 'calendar-page' = Работа → Календар): go back there after saving. */
    public ?string $returnTo = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->returnTo = in_array(request()->query('from'), ['calendar', 'calendar-page'], true) ? request()->query('from') : null;
    }

    protected function getRedirectUrl(): ?string
    {
        return match ($this->returnTo) {
            'calendar' => \Filament\Pages\Dashboard::getUrl(),
            'calendar-page' => \App\Filament\Pages\Calendar::getUrl(),
            default => parent::getRedirectUrl(),
        };
    }
}

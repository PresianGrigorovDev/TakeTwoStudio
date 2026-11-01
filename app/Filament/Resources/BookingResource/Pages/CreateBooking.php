<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BookingResource::normalizeData($data);
    }

    protected function afterCreate(): void
    {
        BookingResource::notifyConflicts($this->record);
    }

    /** Opened from the dashboard calendar: go back there after saving. */
    public bool $fromCalendar = false;

    public function mount(): void
    {
        parent::mount();
        $this->fromCalendar = request()->query('from') === 'calendar';
    }

    protected function getRedirectUrl(): string
    {
        return $this->fromCalendar ? \Filament\Pages\Dashboard::getUrl() : parent::getRedirectUrl();
    }
}

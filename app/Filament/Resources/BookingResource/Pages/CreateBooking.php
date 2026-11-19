<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Pages\Concerns\ReturnsToCalendar;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    use ReturnsToCalendar;

    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BookingResource::normalizeData($data);
    }

    protected function afterCreate(): void
    {
        BookingResource::notifyConflicts($this->record);
    }

    public function mount(): void
    {
        parent::mount();
        $this->rememberCalendar();
    }

    protected function getRedirectUrl(): string
    {
        return $this->calendarReturnUrl ?? parent::getRedirectUrl();
    }
}

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

    /** Opened from a calendar ('calendar' = dashboard, 'calendar-page' = Работа → Календар): go back there after saving. */
    public ?string $returnTo = null;

    public function mount(): void
    {
        parent::mount();
        $this->returnTo = in_array(request()->query('from'), ['calendar', 'calendar-page'], true) ? request()->query('from') : null;
    }

    protected function getRedirectUrl(): string
    {
        return match ($this->returnTo) {
            'calendar' => \Filament\Pages\Dashboard::getUrl(),
            'calendar-page' => \App\Filament\Pages\Calendar::getUrl(),
            default => parent::getRedirectUrl(),
        };
    }
}

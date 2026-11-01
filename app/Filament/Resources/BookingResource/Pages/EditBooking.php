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

    /** Opened from the dashboard calendar: go back there after saving. */
    public bool $fromCalendar = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->fromCalendar = request()->query('from') === 'calendar';
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->fromCalendar ? \Filament\Pages\Dashboard::getUrl() : parent::getRedirectUrl();
    }
}

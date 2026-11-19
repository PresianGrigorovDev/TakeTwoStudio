<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Pages\Concerns\ReturnsToCalendar;
use App\Filament\Resources\FinanceProjectResource;
use App\Models\Booking;
use App\Models\FinanceProject;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    use ReturnsToCalendar;

    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('finance')->label('Финанси')->icon('heroicon-m-banknotes')->color('gray')
                ->visible(fn () => FinanceProject::installed() && Booking::isClientKind($this->record->kind))
                ->url(fn () => FinanceProjectResource::urlForSource($this->record)),
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

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->rememberCalendar();
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->calendarReturnUrl ?? parent::getRedirectUrl();
    }
}

<?php

namespace App\Filament\Resources\BookingResource\Pages\Concerns;

use App\Support\CalendarContext;
use Filament\Actions\Action;
use Livewire\Attributes\Locked;

trait ReturnsToCalendar
{
    #[Locked]
    public ?string $calendarReturnUrl = null;

    protected function rememberCalendar(): void
    {
        $this->calendarReturnUrl = CalendarContext::returnUrl();
    }

    protected function getCancelFormAction(): Action
    {
        $action = parent::getCancelFormAction();

        return $this->calendarReturnUrl ? $action->alpineClickHandler(null)->url($this->calendarReturnUrl) : $action;
    }
}

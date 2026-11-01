<?php

namespace App\Livewire;

use App\Filament\Resources\ServiceResource;
use App\Models\Service;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

/** "Настройки" tab of a service page: the service record itself (name, hero, video, delivery days...). */
class ServiceHubSettings extends Component implements HasForms
{
    use InteractsWithForms;

    public Service $service;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->service->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return ServiceResource::form($form)
            ->model($this->service)
            ->statePath('data');
    }

    public function save(): void
    {
        $this->service->update($this->form->getState());

        Notification::make()->title('Запазено')->success()->send();
    }

    public function render()
    {
        return view('livewire.service-hub-settings');
    }
}

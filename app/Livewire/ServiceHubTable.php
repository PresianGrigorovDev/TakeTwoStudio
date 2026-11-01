<?php

namespace App\Livewire;

use App\Filament\Support\ServiceHub;
use App\Models\Service;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

/** One tab of a service page (packages, extras, photos, FAQ, texts, promotions). */
class ServiceHubTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $serviceId;

    public string $section;

    public function service(): Service
    {
        return Service::findOrFail($this->serviceId);
    }

    public function table(Table $table): Table
    {
        return ServiceHub::table($this->section, $this->service(), $table);
    }

    public function render()
    {
        return view('livewire.service-hub-table');
    }
}

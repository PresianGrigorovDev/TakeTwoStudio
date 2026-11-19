<?php

namespace App\Filament\Resources\FinanceProjectResource\Pages;

use App\Filament\Resources\FinanceProjectResource;
use App\Filament\Support\FinanceForms;
use App\Models\Booking;
use App\Models\Order;
use App\Support\Finance\Projects;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFinanceProject extends CreateRecord
{
    protected static string $resource = FinanceProjectResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $source = Order::find(request()->integer('order')) ?? Booking::find(request()->integer('booking'));
        if ($source) {
            abort_if($source instanceof Booking && ! Booking::isClientKind($source->kind), 404);
            $this->form->fill(Projects::defaults($source));
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return FinanceForms::save(fn () => app(Projects::class)->save(null, $data));
    }
}

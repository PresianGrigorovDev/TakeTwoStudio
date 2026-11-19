<?php

namespace App\Filament\Resources\FinanceProjectResource\Pages;

use App\Filament\Pages\FinanceOverview;
use App\Filament\Resources\FinanceMovementResource;
use App\Filament\Resources\FinanceProjectResource;
use App\Filament\Support\FinanceForms;
use App\Support\Finance\Euro;
use App\Support\Finance\Projects;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFinanceProject extends EditRecord
{
    protected static string $resource = FinanceProjectResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $shares = $this->record->shares;
        $equal = array_sum($shares) === count($shares);
        $data['agreed'] = $this->record->agreed_cents === null ? null : Euro::decimal($this->record->agreed_cents);
        $data['participants'] = collect($shares)->map(fn ($weight, $id) => [
            'account_id' => $id,
            'percentage' => $equal ? null : Euro::decimal($weight),
        ])->values()->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return FinanceForms::save(fn () => app(Projects::class)->save($record, $data));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('movement')->label('Запиши движение')->icon('heroicon-m-plus')
                ->url(fn () => FinanceMovementResource::getUrl('create', ['project' => $this->record->id])),
            Action::make('balances')->label('Салда и изравняване')->color('gray')->icon('heroicon-m-scale')
                ->url(fn () => FinanceOverview::getUrl(['project' => $this->record->id])),
            Action::make('history')->label('История')->color('gray')
                ->url(fn () => FinanceMovementResource::historyUrl($this->record->id)),
        ];
    }
}

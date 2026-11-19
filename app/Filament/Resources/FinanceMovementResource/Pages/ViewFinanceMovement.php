<?php

namespace App\Filament\Resources\FinanceMovementResource\Pages;

use App\Filament\Resources\FinanceMovementResource;
use App\Filament\Resources\FinanceProjectResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewFinanceMovement extends ViewRecord
{
    protected static string $resource = FinanceMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('project')->label('Финансов проект')->color('gray')
                ->visible(fn () => filled($this->record->finance_project_id))
                ->url(fn () => FinanceProjectResource::getUrl('edit', ['record' => $this->record->finance_project_id])),
            Action::make('history')->label('Към движенията')->color('gray')
                ->url(fn () => FinanceMovementResource::historyUrl($this->record->finance_project_id)),
        ];
    }
}

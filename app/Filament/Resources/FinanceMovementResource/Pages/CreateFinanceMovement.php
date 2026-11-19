<?php

namespace App\Filament\Resources\FinanceMovementResource\Pages;

use App\Filament\Resources\FinanceMovementResource;
use App\Filament\Support\FinanceForms;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Support\Finance\Ledger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFinanceMovement extends CreateRecord
{
    protected static string $resource = FinanceMovementResource::class;

    protected static bool $canCreateAnother = false;

    protected ?bool $hasDatabaseTransactions = false;

    public function mount(): void
    {
        parent::mount();

        if ($project = FinanceProject::find(request()->integer('project'))) {
            $kind = request()->query('kind');
            $allowed = $project->isStudio()
                ? [FinanceMovement::EXPENSE, FinanceMovement::TRANSFER]
                : [FinanceMovement::INCOME, FinanceMovement::EXPENSE, FinanceMovement::REFUND, FinanceMovement::TRANSFER];

            $this->form->fill([
                'finance_project_id' => $project->id,
                'kind' => in_array($kind, $allowed, true) ? $kind : $allowed[0],
                'occurred_on' => Ledger::today(),
                'payment_stage' => 'deposit',
                'amount' => is_string(request()->query('amount')) ? request()->query('amount') : null,
                'from_account_id' => request()->integer('from') ?: null,
                'to_account_id' => request()->integer('to') ?: null,
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $project = FinanceProject::findOrFail($data['finance_project_id']);

        return FinanceForms::save(fn () => app(Ledger::class)->record($project, $data, auth()->user()));
    }

    protected function getRedirectUrl(): string
    {
        return FinanceMovementResource::getUrl('view', ['record' => $this->record]);
    }
}

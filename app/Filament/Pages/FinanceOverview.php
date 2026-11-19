<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FinanceMovementResource;
use App\Filament\Resources\FinanceProjectResource;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Support\Finance\Ledger;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class FinanceOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Финанси';

    protected static ?string $navigationLabel = 'Обзор';

    protected static ?string $title = 'Финанси';

    protected static ?string $slug = 'finansi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.finance-overview';

    public ?array $filters = [];

    #[Locked]
    public string $from;

    #[Locked]
    public string $through;

    #[Locked]
    public ?int $projectId = null;

    #[Locked]
    public ?string $settlementDate = null;

    #[Locked]
    public array $settlement = [];

    public static function canAccess(): bool
    {
        return FinanceProject::installed() && (auth()->user()?->isAdmin() ?? false);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->from = today('Europe/Sofia')->startOfMonth()->toDateString();
        $this->through = Ledger::today();
        $this->projectId = FinanceProject::find(request()->integer('project'))?->id;
        $this->form->fill(['from' => $this->from, 'through' => $this->through, 'project' => $this->projectId]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('filters')->columns(3)->schema([
            DatePicker::make('from')->label('От')->required()->maxDate(Ledger::today()),
            DatePicker::make('through')->label('До')->required()->afterOrEqual('from')->maxDate(Ledger::today()),
            Select::make('project')->label('Проект')->placeholder('Всички проекти')
                ->options(fn () => FinanceProject::orderBy('title')->pluck('title', 'id'))
                ->searchable()->exists('finance_projects', 'id'),
        ]);
    }

    public function applyFilters(): void
    {
        $filters = $this->form->getState();
        $this->from = $filters['from'];
        $this->through = $filters['through'];
        $this->projectId = filled($filters['project']) ? (int) $filters['project'] : null;
        unset($this->report, $this->selectedProject);
    }

    #[Computed]
    public function selectedProject(): ?FinanceProject
    {
        return $this->projectId ? FinanceProject::findOrFail($this->projectId) : null;
    }

    #[Computed]
    public function report(): array
    {
        return app(Ledger::class)->report($this->from, $this->through, $this->selectedProject);
    }

    public function settlePeriodAction(): Action
    {
        return Action::make('settlePeriod')->label('Запиши общо изравняване')->icon('heroicon-m-check')
            ->databaseTransaction(false)
            ->visible(fn () => ! $this->projectId && Ledger::settlementPlan($this->report)['adjustments'] !== [])
            ->mountUsing(function (Form $form) {
                abort_if($this->projectId !== null, 403);
                $this->settlementDate = $this->through;
                $this->settlement = Ledger::settlementPlan($this->report);
                $form->fill();
            })
            ->modalHeading('Запис на общото изравняване')
            ->modalDescription(fn () => 'Потвърдете извършените преводи към '.\Illuminate\Support\Carbon::parse($this->settlementDate)->format('d.m.Y').'.')
            ->modalContent(fn () => view('filament.finance.settlement-preview', [
                'accounts' => FinanceAccount::choices(),
                'transfers' => $this->settlement['transfers'] ?? [],
            ]))
            ->form([
                Select::make('method')->label('Начин на плащане')->options(FinanceMovement::METHODS)
                    ->placeholder('Различни начини или прихващане'),
                Checkbox::make('confirmed')->label('Потвърждавам показаното изравняване')->accepted()->required(),
            ])
            ->modalSubmitActionLabel('Запиши изравняването')
            ->action(function (array $data, Action $action) {
                abort_if($this->projectId !== null || ! $this->settlementDate, 403);

                try {
                    app(Ledger::class)->settle($this->settlementDate, Ledger::fingerprint($this->settlement), $data['method'] ?? null, auth()->user());
                } catch (ValidationException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                    $action->halt();

                    return;
                }

                unset($this->report);
                Notification::make()->title('Общото изравняване е записано')->success()->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordMovement')->label('Запиши движение')->icon('heroicon-m-plus')
                ->url(fn () => FinanceMovementResource::getUrl('create', array_filter(['project' => $this->projectId]))),
            Action::make('listMovements')->label('История')->color('gray')
                ->url(fn () => FinanceMovementResource::historyUrl($this->projectId)),
        ];
    }

    protected function getViewData(): array
    {
        $report = $this->report;
        $project = $this->selectedProject;

        return [
            'report' => $report,
            'project' => $project,
            'projectRow' => $project ? $report['projects'][$project->id] : null,
            'accounts' => FinanceAccount::choices(),
            'transfers' => Ledger::transfers($report['balances']),
            'studioUrl' => FinanceProjectResource::getUrl('edit', ['record' => FinanceProject::studio()]),
        ];
    }
}

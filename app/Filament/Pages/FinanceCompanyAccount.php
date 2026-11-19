<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FinanceMovementResource;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Support\Finance\CompanyLedger;
use App\Support\Finance\Ledger;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

class FinanceCompanyAccount extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Финанси';

    protected static ?string $navigationLabel = 'Фирмена сметка';

    protected static ?string $title = 'Фирмена сметка';

    protected static ?string $slug = 'firmena-smetka';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.finance-company-account';

    public ?array $filters = [];

    #[Locked]
    public string $from;

    #[Locked]
    public string $through;

    public static function canAccess(): bool
    {
        return FinanceProject::installed() && (auth()->user()?->isAdmin() ?? false);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->from = today('Europe/Sofia')->startOfMonth()->toDateString();
        $this->through = Ledger::today();
        $this->form->fill(['from' => $this->from, 'through' => $this->through]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('filters')->columns(2)->schema([
            DatePicker::make('from')->label('От')->required()->maxDate(Ledger::today()),
            DatePicker::make('through')->label('До')->required()->afterOrEqual('from')->maxDate(Ledger::today()),
        ]);
    }

    public function applyFilters(): void
    {
        $filters = $this->form->getState();
        $this->from = $filters['from'];
        $this->through = $filters['through'];
        $this->resetPage();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('expense')->label('Запиши разход')->icon('heroicon-m-minus-circle')
                ->url(fn () => FinanceMovementResource::getUrl('create', [
                    'project' => FinanceProject::studio()->id,
                    'kind' => FinanceMovement::EXPENSE,
                    'from' => FinanceAccount::companyId(),
                ])),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'data' => app(CompanyLedger::class)->report($this->from, $this->through, $this->getPage()),
            'names' => FinanceAccount::choices(includeLegacy: true),
        ];
    }
}

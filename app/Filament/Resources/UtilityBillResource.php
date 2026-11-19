<?php

namespace App\Filament\Resources;

use App\Filament\Pages\FinanceOverview;
use App\Filament\Resources\UtilityBillResource\Pages;
use App\Filament\Support\FinanceForms;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Support\Finance\Euro;
use App\Support\Finance\Ledger;
use App\Support\Finance\UtilityBills;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class UtilityBillResource extends Resource
{
    protected static ?string $model = FinanceProject::class;

    protected static ?string $slug = 'utility-bills';

    protected static ?string $navigationGroup = 'Финанси';

    protected static ?string $navigationLabel = 'Битови сметки';

    protected static ?string $modelLabel = 'Битова сметка';

    protected static ?string $pluralModelLabel = 'Битови сметки';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-euro';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return FinanceProject::installed() && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return FinanceProject::installed() && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return $record->isUtility() && $record->utilityExpense && ! $record->utilityExpense->voided_at && parent::canEdit($record);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('kind', FinanceProject::UTILITY)
            ->with(['utilityExpense.fromAccount.teamMember', 'utilityExpense.handler.teamMember']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Плащане')->columns(2)->schema([
                Forms\Components\Select::make('category')->label('Вид сметка')
                    ->options(UtilityBills::CATEGORIES)->required(),
                Forms\Components\TextInput::make('period_label')->label('Период или бележка')
                    ->placeholder('Месец и година')->maxLength(120),
                FinanceForms::amount('amount', 'Сума')->required()->live(onBlur: true),
                Forms\Components\DatePicker::make('occurred_on')->label('Дата на плащане')
                    ->default(Ledger::today())->maxDate(Ledger::today())->required(),
                Forms\Components\Select::make('from_account_id')->label('Кой плати')
                    ->options(fn () => FinanceAccount::choices())->required()->live(),
                Forms\Components\Select::make('method')->label('Начин на плащане')
                    ->options(FinanceMovement::METHODS)->required(),
                Forms\Components\Select::make('handled_by_id')->label('Кой плати през общата сметка')
                    ->options(fn () => FinanceAccount::choices(true))
                    ->visible(fn (Forms\Get $get) => FinanceAccount::whereKey($get('from_account_id'))->where('kind', '!=', 'member')->exists()),
            ]),
            Forms\Components\Section::make('Разпределение')->schema([
                Forms\Components\ToggleButtons::make('split_mode')->label('Подялба')
                    ->options(['equal' => 'По равно', 'custom' => 'Суми по човек'])
                    ->default('equal')->inline()->required()->live()
                    ->afterStateUpdated(function (string $state, Forms\Get $get, Forms\Set $set) {
                        if ($state === 'custom' && blank($get('allocations'))) {
                            $set('allocations', array_map(fn ($id) => ['account_id' => $id, 'amount' => null], $get('members') ?? []));
                        }
                    }),
                Forms\Components\CheckboxList::make('members')->label('Кои участват в подялбата')
                    ->options(fn () => FinanceAccount::choices(true))
                    ->default(fn () => FinanceAccount::activeMembers()->pluck('id')->all())
                    ->columns(['sm' => 3])->required()->live()
                    ->visible(fn (Forms\Get $get) => $get('split_mode') === 'equal'),
                Forms\Components\Repeater::make('allocations')->label('Дялове')
                    ->helperText('Сборът на дяловете трябва да е равен на сумата на сметката.')
                    ->schema([
                        Forms\Components\Select::make('account_id')->label('Участник')
                            ->options(fn () => FinanceAccount::choices(true))->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()->live(),
                        FinanceForms::amount('amount', 'Дял')->required()->live(onBlur: true),
                    ])->columns(2)->defaultItems(0)->required()->reorderable(false)
                    ->addActionLabel('Добави участник')->live()
                    ->visible(fn (Forms\Get $get) => $get('split_mode') === 'custom'),
                Forms\Components\Placeholder::make('reimbursements')->label('Кой колко дължи')
                    ->content(fn (Forms\Get $get, ?FinanceProject $record) => self::preview($get, $record)),
            ]),
        ]);
    }

    private static function preview(Forms\Get $get, ?FinanceProject $record): View
    {
        $payer = (int) $get('from_account_id');
        $accounts = FinanceAccount::choices();
        $payerName = $accounts[$payer] ?? null;
        $transfers = null;
        $message = null;
        $splitMode = $get('split_mode');
        $allocations = $get('allocations') ?? [];

        if (filled($get('amount'))) {
            try {
                $amount = Euro::parse($get('amount'));
                $shares = Ledger::split($amount, UtilityBills::weights($amount, [
                    'split_mode' => $splitMode,
                    'members' => $get('members'),
                    'allocations' => $allocations,
                ]));
                if ($payerName) {
                    $balance = [];
                    if ($record) {
                        $date = Ledger::today();
                        $balance = app(Ledger::class)->report($date, $date, $record)['projects'][$record->id]['held'];
                        $expense = $record->utilityExpense;
                        if ($expense && ! $expense->voided_at) {
                            $previousPayer = $expense->from_account_id;
                            $balance[$previousPayer] = ($balance[$previousPayer] ?? 0) + $expense->amount_cents;
                        }
                    }
                    $balance[$payer] = ($balance[$payer] ?? 0) - $amount;
                    foreach ($shares as $account => $cents) {
                        $balance[$account] = ($balance[$account] ?? 0) + $cents;
                    }
                    ksort($balance);
                    $transfers = Ledger::transfers($balance);
                }
            } catch (ValidationException $exception) {
                if ($splitMode === 'equal' || ($allocations && collect($allocations)->every(fn ($row) => filled($row['account_id'] ?? null) && filled($row['amount'] ?? null)))) {
                    $message = $exception->getMessage();
                }
            }
        }

        return view('filament.finance.utility-bill-preview', compact('payerName', 'accounts', 'transfers', 'message'));
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->recordUrl(fn (FinanceProject $record) => self::balanceUrl($record))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Сметка')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('utilityExpense.occurred_on')->label('Платена на')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('payer')->label('Платил')
                    ->state(fn (FinanceProject $record) => $record->utilityExpense?->fromAccount?->display_name)
                    ->description(fn (FinanceProject $record) => $record->utilityExpense?->handler?->display_name),
                Tables\Columns\TextColumn::make('utilityExpense.amount_cents')->label('Сума')
                    ->weight('semibold')->formatStateUsing(fn ($state) => Euro::format((int) $state)),
                Tables\Columns\TextColumn::make('outstanding')->label('Остава за възстановяване')
                    ->state(fn (FinanceProject $record) => self::outstanding($record))
                    ->listWithLineBreaks()->wrap(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')->label('Състояние')->default(true)
                    ->placeholder('Всички сметки')->trueLabel('Активни')->falseLabel('Изтрити')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('utilityExpense', fn (Builder $expense) => $expense->posted()),
                        false: fn (Builder $query) => $query->whereHas('utilityExpense', fn (Builder $expense) => $expense->whereNotNull('voided_at')),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('balance')->label('Виж разпределението')
                    ->url(fn (FinanceProject $record) => self::balanceUrl($record)),
                Tables\Actions\EditAction::make()->label('Редактирай'),
                Tables\Actions\Action::make('delete')->label('Изтрий')->color('danger')->icon('heroicon-o-trash')
                    ->visible(fn (FinanceProject $record) => self::canEdit($record))->databaseTransaction(false)
                    ->modalHeading('Изтриване на битова сметка')
                    ->modalDescription('Сметката се премества в „Изтрити“. Записаните плащания се пазят, а салдата се преизчисляват.')
                    ->modalSubmitActionLabel('Изтрий сметката')
                    ->form([Forms\Components\Textarea::make('void_reason')->label('Причина')->required()->maxLength(500)])
                    ->action(function (FinanceProject $record, array $data, Tables\Actions\Action $action) {
                        app(Ledger::class)->void($record->utilityExpense, $data['void_reason'], auth()->user());
                        $action->success();
                    })->successNotificationTitle('Сметката е изтрита'),
            ])
            ->emptyStateHeading('Няма записани битови сметки');
    }

    private static function outstanding(FinanceProject $record): array
    {
        $report = once(function () {
            $today = Ledger::today();

            return app(Ledger::class)->report($today, $today);
        });
        $transfers = Ledger::transfers($report['projects'][$record->id]['balance'] ?? []);
        $voided = (bool) $record->utilityExpense?->voided_at;
        if ($transfers === []) {
            return [$voided ? 'Изтрита' : 'Уредена'];
        }

        $accounts = once(fn () => FinanceAccount::choices());
        $lines = array_map(fn (array $transfer) => $accounts[$transfer['from']].' → '.$accounts[$transfer['to']].' · '.Euro::format($transfer['cents']), $transfers);

        return $voided ? ['Изтрита', ...$lines] : $lines;
    }

    public static function balanceUrl(FinanceProject $record): string
    {
        return FinanceOverview::getUrl(['project' => $record->id]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUtilityBills::route('/'),
            'create' => Pages\CreateUtilityBill::route('/create'),
            'edit' => Pages\EditUtilityBill::route('/{record}/edit'),
        ];
    }
}

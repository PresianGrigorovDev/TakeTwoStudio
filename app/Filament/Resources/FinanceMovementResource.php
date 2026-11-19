<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceMovementResource\Pages;
use App\Filament\Support\FinanceForms;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Support\Finance\Euro;
use App\Support\Finance\Ledger;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinanceMovementResource extends Resource
{
    protected static ?string $model = FinanceMovement::class;

    protected static ?string $navigationGroup = 'Финанси';

    protected static ?string $navigationLabel = 'Движения';

    protected static ?string $modelLabel = 'Финансово движение';

    protected static ?string $pluralModelLabel = 'Финансови движения';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return FinanceProject::installed() && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return FinanceProject::installed() && parent::canCreate();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('finance_project_id')->label('Проект или общи разходи')
                    ->relationship('project', 'title')->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $kinds = self::movementKinds((int) $state);
                        if (! isset($kinds[$get('kind')])) {
                            $set('kind', array_key_first($kinds));
                        }
                    })
                    ->columnSpanFull(),
                Forms\Components\Select::make('kind')->label('Вид движение')->required()->live()
                    ->options(fn (Forms\Get $get) => self::movementKinds((int) $get('finance_project_id')))
                    ->default(FinanceMovement::INCOME),
                Forms\Components\DatePicker::make('occurred_on')->label('Дата на плащането')
                    ->default(Ledger::today())->maxDate(Ledger::today())->required(),
                FinanceForms::amount('amount', 'Сума')->required(),
                Forms\Components\Select::make('method')->label('Начин на плащане')->options(FinanceMovement::METHODS)->required(),
                Forms\Components\Select::make('payment_stage')->label('Етап на плащането')
                    ->options(FinanceMovement::STAGES)->default('deposit')->required()
                    ->visible(fn (Forms\Get $get) => $get('kind') === FinanceMovement::INCOME),
                Forms\Components\Select::make('from_account_id')->label(fn (Forms\Get $get) => $get('kind') === FinanceMovement::TRANSFER
                    ? 'Кой даде парите' : 'Кой плати разхода или връщането')
                    ->options(fn () => FinanceAccount::choices())->required()->live()
                    ->visible(fn (Forms\Get $get) => $get('kind') !== FinanceMovement::INCOME),
                Forms\Components\Select::make('to_account_id')->label('Кой получи парите')
                    ->options(fn () => FinanceAccount::choices())->required()->live()
                    ->visible(fn (Forms\Get $get) => in_array($get('kind'), [FinanceMovement::INCOME, FinanceMovement::TRANSFER], true)),
                Forms\Components\Select::make('handled_by_id')->label('Кой извърши движението през общата сметка')
                    ->options(fn () => FinanceAccount::choices(true))
                    ->visible(fn (Forms\Get $get) => FinanceAccount::whereIn('id', array_filter([
                        $get('kind') !== FinanceMovement::INCOME ? $get('from_account_id') : null,
                        in_array($get('kind'), [FinanceMovement::INCOME, FinanceMovement::TRANSFER], true) ? $get('to_account_id') : null,
                    ]))->where('kind', '!=', 'member')->exists()),
                Forms\Components\Textarea::make('description')->label('Описание')->maxLength(500)
                    ->placeholder('Например албум, транспорт или референция на превода')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    private static function movementKinds(int $project): array
    {
        if ($project && FinanceProject::find($project)?->isStudio()) {
            return array_intersect_key(FinanceMovement::KINDS, array_flip([FinanceMovement::EXPENSE, FinanceMovement::TRANSFER]));
        }

        return array_diff_key(FinanceMovement::KINDS, [FinanceMovement::SETTLEMENT => true]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            ViewEntry::make('summary')->hiddenLabel()
                ->view('filament.finance.movement-detail')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->with(['project', 'fromAccount.teamMember', 'toAccount.teamMember', 'handler.teamMember']))
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('occurred_on')->orderByDesc('id'))
            ->columns([
                Tables\Columns\TextColumn::make('occurred_on')->label('Дата')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('kind')->label('Вид')->badge()->wrap()
                    ->formatStateUsing(fn ($state) => FinanceMovement::KINDS[$state]),
                Tables\Columns\TextColumn::make('amount_cents')->label('Сума')->weight('semibold')
                    ->formatStateUsing(fn ($state) => Euro::format((int) $state))->sortable(),
                Tables\Columns\TextColumn::make('project.title')->label('Проект')->placeholder('Общо за периода')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('sender')->label('Платил')
                    ->state(fn (FinanceMovement $record) => $record->fromAccount?->display_name
                        ?? ($record->kind === FinanceMovement::INCOME ? 'Клиент' : '—')),
                Tables\Columns\TextColumn::make('receiver')->label('Получил')
                    ->state(fn (FinanceMovement $record) => $record->toAccount?->display_name
                        ?? ($record->kind === FinanceMovement::REFUND ? 'Клиент' : '—')),
                Tables\Columns\TextColumn::make('handler_name')->label('Извършил')
                    ->state(fn (FinanceMovement $record) => $record->handler?->display_name)
                    ->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('recorded_by_name')->label('Въвел')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('description')->label('Описание')->searchable()
                    ->limit(55)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->label('Статус')->badge()
                    ->state(fn (FinanceMovement $record) => $record->voided_at ? 'Анулирано' : 'Записано')
                    ->color(fn (FinanceMovement $record) => $record->voided_at ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project')->label('Проект')->relationship('project', 'title')->searchable(),
                Tables\Filters\SelectFilter::make('kind')->label('Вид движение')->options(FinanceMovement::KINDS),
                Tables\Filters\Filter::make('period')->label('Период')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('От'),
                        Forms\Components\DatePicker::make('through')->label('До'),
                    ])->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->where('occurred_on', '>=', $date))
                        ->when($data['through'] ?? null, fn (Builder $query, string $date) => $query->where('occurred_on', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Преглед'),
                Tables\Actions\Action::make('void')->label('Анулирай')->color('danger')
                    ->visible(fn (FinanceMovement $record) => ! $record->voided_at)
                    ->modalHeading('Анулирай движение')
                    ->modalDescription('Салдата ще се преизчислят. Записът и причината ще останат в историята.')
                    ->form([Forms\Components\Textarea::make('void_reason')->label('Причина')->required()->maxLength(500)])
                    ->action(fn (FinanceMovement $record, array $data) => app(Ledger::class)
                        ->void($record, $data['void_reason'], auth()->user()))
                    ->successNotificationTitle('Движението е анулирано'),
            ]);
    }

    public static function historyUrl(?int $project = null): string
    {
        return static::getUrl('index', $project ? ['tableFilters' => ['project' => ['value' => $project]]] : []);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceMovements::route('/'),
            'create' => Pages\CreateFinanceMovement::route('/create'),
            'view' => Pages\ViewFinanceMovement::route('/{record}'),
        ];
    }
}

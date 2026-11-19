<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceProjectResource\Pages;
use App\Filament\Support\FinanceForms;
use App\Models\Booking;
use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Support\Finance\Euro;
use App\Support\Finance\Projects;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinanceProjectResource extends Resource
{
    protected static ?string $model = FinanceProject::class;

    protected static ?string $navigationGroup = 'Финанси';

    protected static ?string $navigationLabel = 'Проекти';

    protected static ?string $modelLabel = 'Финансов проект';

    protected static ?string $pluralModelLabel = 'Финансови проекти';

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return FinanceProject::installed() && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return FinanceProject::installed() && parent::canCreate();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', '!=', FinanceProject::UTILITY)
            ->withSum(['movements as received_cents' => fn ($query) => $query->posted()->where('kind', FinanceMovement::INCOME)], 'amount_cents')
            ->withSum(['movements as refunded_cents' => fn ($query) => $query->posted()->where('kind', FinanceMovement::REFUND)], 'amount_cents')
            ->withSum(['movements as expense_cents' => fn ($query) => $query->posted()->where('kind', FinanceMovement::EXPENSE)], 'amount_cents');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Проект')->columns(2)->schema([
                Forms\Components\Select::make('order_id')->label('Поръчка')
                    ->relationship('order', 'name')->searchable()->live()
                    ->hidden(fn (Forms\Get $get, string $operation) => $operation !== 'create' || filled($get('booking_id')))
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => self::fillSource(Order::find($state), $set)),
                Forms\Components\Select::make('booking_id')->label('Самостоятелен ангажимент')
                    ->relationship('booking', 'name', fn (Builder $query) => $query
                        ->whereIn('kind', Booking::CLIENT_KINDS)->whereNull('order_id'))
                    ->searchable()->live()
                    ->hidden(fn (Forms\Get $get, string $operation) => $operation !== 'create' || filled($get('order_id')))
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => self::fillSource(Booking::find($state), $set)),
                Forms\Components\TextInput::make('title')->label('Име на проекта')->required()->maxLength(255),
                FinanceForms::amount('agreed', 'Договорена сума')->hidden(fn (?FinanceProject $record) => $record?->isStudio() ?? false),
                Forms\Components\Placeholder::make('received')->label('Платено от клиента')
                    ->content(fn (FinanceProject $record) => Euro::format(self::paid($record)))
                    ->visible(fn (?FinanceProject $record) => $record && ! $record->isStudio()),
                Forms\Components\Placeholder::make('remaining')->label('Остава от клиента')
                    ->content(fn (FinanceProject $record) => self::remaining($record))
                    ->visible(fn (?FinanceProject $record) => $record && ! $record->isStudio()),
            ]),
            Forms\Components\Section::make('Разпределение на сумата след разходите')
                ->description(fn (?FinanceProject $record) => $record?->isStudio()
                    ? 'Избраните дялове ще важат за следващите общи разходи. При празен списък те се делят по равно между активните хора от екипа.'
                    : 'Оставете процентите празни за равни дялове. При различни дялове въведете проценти със сбор 100%.')
                ->schema([
                    Forms\Components\Repeater::make('participants')->hiddenLabel()
                        ->schema([
                            Forms\Components\Select::make('account_id')->label('Участник')
                                ->options(fn () => FinanceAccount::choices(true))->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            FinanceForms::amount('percentage', 'Дял', '%'),
                        ])->columns(2)->defaultItems(0)->reorderable(false)->addActionLabel('Добави участник'),
                ]),
        ]);
    }

    private static function fillSource(Order|Booking|null $source, Forms\Set $set): void
    {
        if ($source) {
            foreach (Projects::defaults($source) as $field => $value) {
                $set($field, $value);
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Проект')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('kind')->label('Източник')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'order' => 'Поръчка',
                        'booking' => 'Ангажимент',
                        default => 'Студио',
                    }),
                Tables\Columns\TextColumn::make('agreed_cents')->label('Договорено')
                    ->formatStateUsing(fn ($state) => Euro::format((int) $state))->placeholder('—'),
                Tables\Columns\TextColumn::make('paid')->label('Действително платено')
                    ->state(fn (FinanceProject $record) => $record->isStudio() ? '—' : Euro::format(self::paid($record))),
                Tables\Columns\TextColumn::make('unpaid')->label('Остава от клиента')
                    ->state(fn (FinanceProject $record) => self::remaining($record)),
                Tables\Columns\TextColumn::make('expense_total')->label('Разходи')
                    ->state(fn (FinanceProject $record) => Euro::format((int) $record->expense_cents)),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Отвори')]);
    }

    private static function paid(FinanceProject $project): int
    {
        return (int) $project->received_cents - (int) $project->refunded_cents;
    }

    private static function remaining(FinanceProject $project): string
    {
        if ($project->agreed_cents === null) {
            return '—';
        }

        $remaining = $project->agreed_cents - self::paid($project);

        return $remaining < 0 ? 'Надплатено '.Euro::format(-$remaining) : Euro::format($remaining);
    }

    public static function urlForSource(Order|Booking $source): string
    {
        $project = $source instanceof Order ? FinanceProject::forOrder($source) : FinanceProject::forBooking($source);

        return $project
            ? self::getUrl('edit', ['record' => $project])
            : self::getUrl('create', [$source instanceof Order ? 'order' : 'booking' => $source->id]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceProjects::route('/'),
            'create' => Pages\CreateFinanceProject::route('/create'),
            'edit' => Pages\EditFinanceProject::route('/{record}/edit'),
        ];
    }
}

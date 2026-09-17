<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GameEventResource;
use App\Filament\Resources\GameEventResource\Widgets\GameOverviewWidget;
use App\Filament\Resources\GameEventResource\Widgets\GameStatsWidget;
use App\Http\Controllers\GameController;
use App\Models\GameEvent;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

/**
 * "QR Игра – статистика": headline numbers, funnel per sticker location and the
 * game links (base links + per-sticker generator) so the studio can copy them
 * for QR codes. Lives in the Маркетинг navigation group next to the events list.
 */
class GameStats extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'QR Игра – статистика';

    protected static ?string $title = 'QR Игра – статистика';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'game-stats';

    protected static string $view = 'filament.pages.game-stats';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['target' => 'prom', 'loc' => '']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('target')
                    ->label('Игра')
                    ->options(GameEventResource::TARGET_LABELS)
                    ->default('prom')
                    ->required()
                    ->native(false)
                    ->live(),

                TextInput::make('loc')
                    ->label('Локация на стикера (loc)')
                    ->placeholder('напр. mg, morska, sevastopol')
                    ->maxLength(40)
                    ->helperText('Малки латински букви, цифри и тире – до 40 знака. Празно = линк без локация.')
                    ->live(debounce: 400),
            ])
            ->columns(2)
            ->statePath('data');
    }

    /** The link for the target + loc currently in the form (loc sanitized the same way as the public page). */
    public function getGeneratedUrl(): string
    {
        return GameController::url((string) ($this->data['target'] ?? 'prom'), $this->data['loc'] ?? null);
    }

    /** @return array<string,string> label => url */
    public function getBaseLinks(): array
    {
        $links = [];

        foreach (GameEventResource::TARGET_LABELS as $target => $label) {
            $links[$label] = GameController::url($target);
        }

        return $links;
    }

    /**
     * Sticker locations that have already been scanned at least once, with their links.
     *
     * @return array<int,array{target:string,label:string,loc:string,scans:int,url:string}>
     */
    public function getKnownLocations(): array
    {
        return GameEvent::query()
            ->selectRaw("target, loc, SUM(CASE WHEN event = 'scan' THEN 1 ELSE 0 END) AS scans")
            ->whereNotNull('loc')
            ->groupBy('target', 'loc')
            ->orderBy('target')
            ->orderByDesc('scans')
            ->orderBy('loc')
            ->get()
            ->map(fn ($row) => [
                'target' => $row->target,
                'label' => GameEventResource::TARGET_LABELS[$row->target] ?? $row->target,
                'loc' => $row->loc,
                'scans' => (int) $row->scans,
                'url' => GameController::url($row->target, $row->loc),
            ])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('events')
                ->label('Всички събития и кодове')
                ->icon('heroicon-m-table-cells')
                ->url(GameEventResource::getUrl()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GameOverviewWidget::class,
            GameStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}

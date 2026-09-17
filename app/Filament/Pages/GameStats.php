<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GameEventResource;
use App\Filament\Resources\GameEventResource\Widgets\GameOverviewWidget;
use App\Filament\Resources\GameEventResource\Widgets\GameStatsWidget;
use App\Filament\Resources\GameStickerResource;
use App\Http\Controllers\GameController;
use App\Models\GameEvent;
use App\Models\GameSticker;
use App\Models\SiteSetting;
use App\Support\GameVoucher;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * "QR Игра – статистика": headline numbers, funnel per sticker location, the list of
 * locations that were actually scanned (linked to their sticker, or offering to create one)
 * and the game settings (discount at win / after sharing, per game).
 * Links and QR codes themselves are managed in Маркетинг → QR стикери (GameStickerResource).
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
    public ?array $settings = [];

    /** @var array<string,array{target:string,level:string,label:string}> form field => setting */
    private const FIELDS = [
        'prom_base' => ['target' => 'prom', 'level' => 'base', 'label' => 'Отстъпка при победа – бал'],
        'prom_shared' => ['target' => 'prom', 'level' => 'shared', 'label' => 'Отстъпка след споделяне – бал'],
        'wedding_base' => ['target' => 'wedding', 'level' => 'base', 'label' => 'Отстъпка при победа – сватба'],
        'wedding_shared' => ['target' => 'wedding', 'level' => 'shared', 'label' => 'Отстъпка след споделяне – сватба'],
    ];

    public function mount(): void
    {
        $state = [];
        foreach (self::FIELDS as $field => $def) {
            $state[$field] = GameVoucher::percentFor($def['target'], $def['level']);
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        $percent = fn (string $name, string $label) => TextInput::make($name)
            ->label($label)
            ->numeric()
            ->integer()
            ->minValue(0)
            ->maxValue(GameVoucher::MAX_PERCENT)
            ->suffix('%')
            ->required();

        return $form
            ->schema([
                Fieldset::make('Абитуриентски бал')
                    ->schema([
                        $percent('prom_base', 'При победа'),
                        $percent('prom_shared', 'След споделяне на Story')->gte('prom_base'),
                    ])
                    ->columns(2),

                Fieldset::make('Сватба')
                    ->schema([
                        $percent('wedding_base', 'При победа'),
                        $percent('wedding_shared', 'След споделяне на Story')->gte('wedding_base'),
                    ])
                    ->columns(2),
            ])
            ->statePath('settings');
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();

        foreach (self::FIELDS as $field => $def) {
            SiteSetting::query()->updateOrCreate(
                ['setting_key' => GameVoucher::SETTING_KEYS[$def['target']][$def['level']]],
                ['setting_value' => (string) (int) $data[$field], 'description' => 'QR игра: '.$def['label'].' (%)'],
            );
        }

        Notification::make()
            ->title('Настройките на играта са запазени')
            ->body('Важат за ваучерите, издадени от сега нататък. Вече издадените пазят процента, който играчът е видял.')
            ->success()
            ->send();
    }

    /**
     * Sticker locations that have been scanned at least once, with their sticker (if registered).
     *
     * @return array<int,array{target:string,label:string,loc:string,scans:int,url:string,sticker:?GameSticker,createUrl:string}>
     */
    public function getKnownLocations(): array
    {
        $stickers = GameSticker::query()->get()->keyBy(fn (GameSticker $s) => $s->target.'|'.$s->loc);

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
                'sticker' => $stickers->get($row->target.'|'.$row->loc),
                'createUrl' => GameStickerResource::getUrl('create', ['target' => $row->target, 'loc' => $row->loc]),
            ])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stickers')
                ->label('QR стикери')
                ->icon('heroicon-m-qr-code')
                ->url(GameStickerResource::getUrl()),

            Action::make('events')
                ->label('Всички събития и кодове')
                ->icon('heroicon-m-table-cells')
                ->color('gray')
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

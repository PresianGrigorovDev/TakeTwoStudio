<?php

namespace App\Filament\Pages;

use App\Enums\ContractType;
use App\Models\TeamMember;
use App\Services\ContractPdfService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ContractGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Генератор на Договори';
    protected static ?string $title = 'Генератор на Договори';
    protected static ?string $navigationGroup = 'Настройки на сайта';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.contract-generator';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'studio_name' => config('contracts.studio.name', 'Take Two Studio 1603'),
            'studio_bulstat' => config('contracts.studio.bulstat', ''),
            'studio_address' => config('contracts.studio.address', ''),
            'studio_mol' => config('contracts.studio.mol', ''),
            'studio_iban' => config('contracts.studio.iban', ''),
            'studio_bank' => config('contracts.studio.bank', ''),
            'contract_date' => now()->format('Y-m-d'),
            'currency' => 'EUR',
            'payment_method' => 'mixed',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    $this->getContractTypeStep(),
                    $this->getExecutorStep(),
                    $this->getClientDataStep(),
                    $this->getEventDetailsStep(),
                    $this->getServicesAndPricingStep(),
                    $this->getContractSectionsStep(),
                ])
                ->persistStepInQueryString('step')
                ->submitAction(view('filament.pages.partials.contract-actions')),
            ])
            ->statePath('data');
    }

    // ─── Step 1: Contract Type ───────────────────────────────────

    private function getContractTypeStep(): Wizard\Step
    {
        return Wizard\Step::make('Тип Договор')
            ->icon('heroicon-o-document-duplicate')
            ->description('Изберете тип на договора')
            ->schema([
                Select::make('contract_type')
                    ->label('Тип на договора')
                    ->options(
                        collect(ContractType::cases())
                            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                    )
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state) {
                            $this->loadDefaultTexts($set, $state);
                        }
                    }),

                TextInput::make('contract_number')
                    ->label('Номер на договора')
                    ->placeholder('Оставете празно за автоматично генериране')
                    ->helperText('Пример: TTS-W-2026-04-10'),

                DatePicker::make('contract_date')
                    ->label('Дата на договора')
                    ->required(),
            ])
            ->columns(2);
    }

    // ─── Step 2: Executor ────────────────────────────────────────

    private function getExecutorStep(): Wizard\Step
    {
        return Wizard\Step::make('Изпълнител')
            ->icon('heroicon-o-user-group')
            ->description('Данни на студиото и изпълнители')
            ->schema([
                Section::make('Данни на студиото')
                    ->schema([
                        TextInput::make('studio_name')
                            ->label('Име на студиото')
                            ->required(),
                        TextInput::make('studio_bulstat')
                            ->label('БУЛСТАТ'),
                        TextInput::make('studio_address')
                            ->label('Адрес'),
                        TextInput::make('studio_mol')
                            ->label('МОЛ (представляващ)'),
                        TextInput::make('studio_iban')
                            ->label('IBAN'),
                        TextInput::make('studio_bank')
                            ->label('Банка'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Select::make('executor_ids')
                    ->label('Изпълнител/и (фотограф/видеограф)')
                    ->multiple()
                    ->options(
                        TeamMember::where('is_active', true)
                            ->orderBy('display_order')
                            ->get()
                            ->mapWithKeys(fn ($m) => [$m->id => "{$m->name} ({$m->role_bg})"])
                    )
                    ->required()
                    ->minItems(1)
                    ->maxItems(3)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText('Изберете от 1 до 3 изпълнители'),

                Section::make('Данни на избраните изпълнители')
                    ->schema([
                        Repeater::make('executors_preview')
                            ->label('')
                            ->schema([
                                TextInput::make('name')->label('Име')->disabled()->dehydrated(false),
                                TextInput::make('role')->label('Роля')->disabled()->dehydrated(false),
                                TextInput::make('phone')->label('Телефон')->disabled()->dehydrated(false),
                                TextInput::make('email')->label('Имейл')->disabled()->dehydrated(false),
                                TextInput::make('address')->label('Адрес')->disabled()->dehydrated(false),
                            ])
                            ->columns(3)
                            ->deletable(false)
                            ->addable(false)
                            ->reorderable(false)
                            ->default([]),
                    ])
                    ->visible(fn (Get $get) => !empty($get('executor_ids'))),
            ]);
    }

    // ─── Step 3: Client Data ─────────────────────────────────────

    private function getClientDataStep(): Wizard\Step
    {
        return Wizard\Step::make('Данни на Клиента')
            ->icon('heroicon-o-identification')
            ->description('Въведете данни на възложителя')
            ->schema([
                Section::make('Възложител 1')
                    ->schema([
                        TextInput::make('client1_name')
                            ->label('Три имена'),
                        TextInput::make('client1_egn')
                            ->label('ЕГН')
                            ->maxLength(10),
                        TextInput::make('client1_address')
                            ->label('Адрес'),
                        TextInput::make('client1_phone')
                            ->label('Телефон')
                            ->tel(),
                        TextInput::make('client1_email')
                            ->label('Имейл')
                            ->email(),
                    ])
                    ->columns(2),

                Section::make('Възложител 2')
                    ->schema([
                        TextInput::make('client2_name')
                            ->label('Три имена'),
                        TextInput::make('client2_egn')
                            ->label('ЕГН')
                            ->maxLength(10),
                        TextInput::make('client2_address')
                            ->label('Адрес'),
                        TextInput::make('client2_phone')
                            ->label('Телефон')
                            ->tel(),
                        TextInput::make('client2_email')
                            ->label('Имейл')
                            ->email(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('contract_type') === ContractType::Wedding->value),

                Section::make('Данни на фирмата')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Наименование на фирмата'),
                        TextInput::make('company_bulstat')
                            ->label('БУЛСТАТ / ЕИК'),
                        TextInput::make('company_address')
                            ->label('Адрес на фирмата'),
                        TextInput::make('company_mol')
                            ->label('МОЛ (представляващ)'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('contract_type') === ContractType::Event->value),
            ]);
    }

    // ─── Step 4: Event Details ───────────────────────────────────

    private function getEventDetailsStep(): Wizard\Step
    {
        return Wizard\Step::make('Детайли на Събитието')
            ->icon('heroicon-o-calendar-days')
            ->description('Дата, час и локации')
            ->schema([
                DatePicker::make('event_date')
                    ->label('Дата на събитието')
                    ->required(),

                Section::make('Часове и локации')
                    ->schema([
                        // Wedding: подготовка
                        Select::make('preparation_time')
                            ->label('Час на подготовка')
                            ->options(self::getTimeOptions())
                            ->searchable()
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Wedding->value),
                        TextInput::make('preparation_location')
                            ->label('Локация на подготовка')
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Wedding->value),

                        // Prom: единствено поле за хотел/локация
                        TextInput::make('prom_venue')
                            ->label('Хотел / Локация на бала')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Prom->value),

                        // Prom: начален час на бала
                        Select::make('start_time')
                            ->label('Начален час')
                            ->options(self::getTimeOptions())
                            ->searchable()
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Prom->value),

                        // Wedding / Event / Custom: церемония / начало
                        Select::make('ceremony_time')
                            ->label('Час на церемония / начало')
                            ->options(self::getTimeOptions())
                            ->searchable()
                            ->visible(fn (Get $get) => $get('contract_type') !== ContractType::Prom->value),
                        TextInput::make('ceremony_location')
                            ->label('Локация на церемония / събитие')
                            ->visible(fn (Get $get) => $get('contract_type') !== ContractType::Prom->value),

                        // Wedding: фотосесия
                        Select::make('photoshoot_time')
                            ->label('Час на фотосесия')
                            ->options(self::getTimeOptions())
                            ->searchable()
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Wedding->value),
                        TextInput::make('photoshoot_location')
                            ->label('Локация на фотосесия')
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Wedding->value),

                        // Wedding + Prom: ресторант / парти
                        Select::make('reception_time')
                            ->label('Час на тържество / парти')
                            ->options(self::getTimeOptions())
                            ->searchable()
                            ->visible(fn (Get $get) => in_array($get('contract_type'), [
                                ContractType::Wedding->value,
                                ContractType::Prom->value,
                            ])),
                        TextInput::make('reception_location')
                            ->label('Локация на тържество / парти')
                            ->visible(fn (Get $get) => in_array($get('contract_type'), [
                                ContractType::Wedding->value,
                                ContractType::Prom->value,
                            ])),

                        // Общо: краен час
                        Select::make('end_time')
                            ->label('Краен час')
                            ->options(self::getTimeOptions())
                            ->searchable(),

                        // Event: тип събитие
                        Select::make('event_type_detail')
                            ->label('Тип събитие')
                            ->options([
                                'Концерт' => 'Концерт',
                                'Конференция' => 'Конференция',
                                'Лансиране' => 'Лансиране',
                                'Корпоративно парти' => 'Корпоративно парти',
                                'Друго' => 'Друго',
                            ])
                            ->visible(fn (Get $get) => $get('contract_type') === ContractType::Event->value),
                    ])
                    ->columns(2),

                Textarea::make('event_notes')
                    ->label('Допълнителни бележки за събитието')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    // ─── Step 5: Services & Pricing ──────────────────────────────

    private function getServicesAndPricingStep(): Wizard\Step
    {
        return Wizard\Step::make('Услуги и Цена')
            ->icon('heroicon-o-currency-euro')
            ->description('Включени услуги и стойност')
            ->schema([
                Repeater::make('services')
                    ->label('Включени услуги')
                    ->schema([
                        TextInput::make('description')
                            ->label('Описание на услугата')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->suffix('€'),
                    ])
                    ->columns(3)
                    ->addActionLabel('Добави услуга')
                    ->reorderable()
                    ->defaultItems(1)
                    ->columnSpanFull(),

                Section::make('Обща стойност')
                    ->schema([
                        TextInput::make('total_price')
                            ->label('Обща цена')
                            ->numeric()
                            ->required(fn (Get $get) => $get('contract_type') !== ContractType::Prom->value)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($state) {
                                    $deposit = round((float) $state * 0.5, 2);
                                    $set('deposit_amount', $deposit);
                                    $set('remaining_amount', (float) $state - $deposit);
                                }
                            }),
                        TextInput::make('deposit_amount')
                            ->label('Капаро (задатък)')
                            ->numeric()
                            ->helperText('По подразбиране 50% от общата цена')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $total = $get('total_price');
                                if ($total && $state) {
                                    $set('remaining_amount', (float) $total - (float) $state);
                                }
                            }),
                        TextInput::make('remaining_amount')
                            ->label('Остатък за плащане')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        Select::make('currency')
                            ->label('Валута')
                            ->options([
                                'EUR' => 'EUR (Ев��о)',
                                'BGN' => 'BGN (Лев)',
                            ])
                            ->default('EUR'),
                        Select::make('payment_method')
                            ->label('Начин на плащане')
                            ->options([
                                'cash' => 'В брой',
                                'bank_transfer' => 'По банков път',
                                'mixed' => 'Смесено',
                            ])
                            ->default('mixed'),
                    ])
                    ->columns(2),
            ]);
    }

    // ─── Step 6: Editable Contract Sections ──────────────────────

    private function getContractSectionsStep(): Wizard\Step
    {
        return Wizard\Step::make('Текст на Договора')
            ->icon('heroicon-o-pencil-square')
            ->description('Преглед и редактиране на клаузите')
            ->schema(function (Get $get): array {
                $type = $get('contract_type');
                if (!$type) {
                    return [
                        TextInput::make('no_type_placeholder')
                            ->label('')
                            ->placeholder('Моля изберете тип на договора в Стъпка 1')
                            ->disabled()
                            ->columnSpanFull(),
                    ];
                }

                $configSections = config("contracts.{$type}", []);
                $components = [];

                $sectionLabels = [
                    'subject' => 'Предмет на договора',
                    'scope_photo' => 'Обхват Фото',
                    'scope_video' => 'Обхват Видео',
                    'scope' => 'Обхват на услугата',
                    'deadlines' => 'Срокове за предаване',
                    'payment' => 'Условия за плащане',
                    'transport' => 'Транспорт и пътни разходи',
                    'overtime' => 'Овъртайм',
                    'cancellation' => 'Отказ и неустойки',
                    'delays' => 'Закъснения',
                    'raw_materials' => 'RAW материали',
                    'copyright' => 'Права и авторство',
                    'limitations' => 'Ограничения на отговорността',
                    'work_conditions' => 'Условия на работа',
                    'force_majeure' => 'Форсмажорни обстоятелства',
                    'obligations_executor' => 'Задължения на Изпълнителя',
                    'obligations_client' => 'Задължения на Възложителя',
                    'obligations' => 'Права и задължения',
                    'general' => 'Общи клаузи',
                ];

                foreach ($configSections as $key => $defaultText) {
                    $label = $sectionLabels[$key] ?? $key;
                    $components[] = $this->makeEditableSection($key, $label, $defaultText);
                }

                return $components;
            });
    }

    private function makeEditableSection(string $key, string $label, string $defaultText): Section
    {
        return Section::make($label)
            ->schema([
                Toggle::make("include_{$key}")
                    ->label('Включи в договора')
                    ->default(true)
                    ->live()
                    ->inline(false),

                Toggle::make("use_custom_{$key}")
                    ->label('Редактирай текста')
                    ->default(false)
                    ->live()
                    ->inline(false),

                Textarea::make("section_{$key}")
                    ->label('')
                    ->rows(5)
                    ->default($defaultText)
                    ->disabled(fn (Get $get) => !$get("use_custom_{$key}"))
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed();
    }

    // ─── Actions ─────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Преглед (PDF)')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(function () {
                    $data = $this->form->getState();
                    $service = app(ContractPdfService::class);
                    $pdf = $service->generate($data);
                    $filename = $service->getFilename($data);

                    return response()->streamDownload(
                        function () use ($pdf) { echo $pdf->output(); },
                        $filename,
                        ['Content-Type' => 'application/pdf', 'Content-Disposition' => "inline; filename=\"{$filename}\""]
                    );
                }),

            Action::make('download')
                ->label('Изтегли PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $data = $this->form->getState();
                    $service = app(ContractPdfService::class);
                    $pdf = $service->generate($data);
                    $filename = $service->getFilename($data);

                    return response()->streamDownload(
                        function () use ($pdf) { echo $pdf->output(); },
                        $filename
                    );
                }),
        ];
    }

    // ─── Helpers ───────────────────────────��─────────────────────

    private function loadDefaultTexts(Set $set, string $type): void
    {
        $configSections = config("contracts.{$type}", []);

        foreach ($configSections as $key => $text) {
            $set("section_{$key}", $text);
            $set("use_custom_{$key}", false);
            $set("include_{$key}", true);
        }
    }

    private static function getTimeOptions(): array
    {
        $options = [];
        for ($h = 6; $h <= 23; $h++) {
            foreach ([0, 30] as $m) {
                $val = sprintf('%02d:%02d', $h, $m);
                $options[$val] = $val;
            }
        }
        return $options;
    }

    public function updatedData(): void
    {
        // Auto-populate executor preview when executor_ids changes
        $executorIds = $this->data['executor_ids'] ?? [];
        if (!empty($executorIds)) {
            $executors = TeamMember::whereIn('id', $executorIds)->get();
            $this->data['executors_preview'] = $executors->map(fn ($e) => [
                'name' => $e->name,
                'role' => $e->role_bg,
                'phone' => $e->phone ?? '-',
                'email' => $e->email ?? '-',
                'address' => $e->address ?? '-',
            ])->toArray();
        } else {
            $this->data['executors_preview'] = [];
        }
    }
}

@php
    use App\Filament\Resources\FinanceMovementResource;
    use App\Filament\Resources\FinanceProjectResource;
    use App\Support\Finance\Euro;

    $period = $report['period'];
    $dateLabel = \Illuminate\Support\Carbon::parse($through)->format('d.m.Y');
    $received = $period['received'] - $period['refunded'];
    $balances = array_filter($report['balances']);
@endphp

<x-filament-panels::page>
    <div class="tt-finance tt-finance__stack">
        <x-filament::section>
            <form wire:submit="applyFilters" class="tt-finance__filters">
                {{ $this->form }}
                <x-filament::button type="submit" color="gray" wire:target="applyFilters">Покажи</x-filament::button>
            </form>
        </x-filament::section>

        @if($project)
            <div class="tt-finance__project">
                <h2>{{ $project->title }}</h2>
                <x-filament::link :href="FinanceProjectResource::getUrl('edit', ['record' => $project])">Към проекта</x-filament::link>
            </div>
        @endif

        <x-filament::section>
            <x-slot name="heading">Движения в избрания период</x-slot>
            <dl class="tt-finance__metrics">
                <div>
                    <dt>Получено от клиенти</dt>
                    <dd>{{ Euro::format($period['received']) }}</dd>
                </div>
                <div>
                    <dt>Платени разходи</dt>
                    <dd>{{ Euro::format($period['expenses']) }}</dd>
                </div>
                <div>
                    <dt>Върнато на клиенти</dt>
                    <dd>{{ Euro::format($period['refunded']) }}</dd>
                </div>
                <div>
                    <dt>Резултат след разходите</dt>
                    <dd>{{ Euro::format($received - $period['expenses']) }}</dd>
                </div>
            </dl>
            @if($period['transfers'])
                <div class="tt-finance__row tt-finance__summary">
                    <span>Записани вътрешни преводи</span>
                    <strong>{{ Euro::format($period['transfers']) }}</strong>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Клиентски плащания към {{ $dateLabel }}</x-slot>
            @if($project && ! $project->isStudio())
                <dl class="tt-finance__metrics">
                    <div><dt>Договорено</dt><dd>{{ $project->agreed_cents === null ? '—' : Euro::format($project->agreed_cents) }}</dd></div>
                    <div><dt>Платено след връщания</dt><dd>{{ Euro::format($projectRow['paid']) }}</dd></div>
                    <div>
                        <dt>{{ ($projectRow['remaining'] ?? 0) < 0 ? 'Надплатено' : 'Остава от клиента' }}</dt>
                        <dd>{{ $projectRow['remaining'] === null ? '—' : Euro::format(abs($projectRow['remaining'])) }}</dd>
                    </div>
                </dl>
            @elseif($project)
                <p class="tt-finance__muted">Тук се отчитат общите разходи на студиото.</p>
            @else
                <div class="tt-finance__row">
                    <span>Остава за получаване по договорени проекти</span>
                    <strong>{{ Euro::format($report['unpaid']) }}</strong>
                </div>
            @endif
        </x-filament::section>

        <div class="tt-finance__columns">
            <x-filament::section>
                <x-slot name="heading">Салда към {{ $dateLabel }}</x-slot>
                <x-slot name="description">Включват по-ранни суми за уреждане.</x-slot>

                @forelse($balances as $account => $balance)
                    <div class="tt-finance__row">
                        <span>{{ $accounts[$account] }}</span>
                        <strong>{{ $balance > 0 ? 'Да даде' : 'Да получи' }} · {{ Euro::format(abs($balance)) }}</strong>
                    </div>
                @empty
                    <p class="tt-finance__muted">Няма суми за уреждане.</p>
                @endforelse

                @if($projectRow && $projectRow['shares'])
                    <div class="tt-finance__summary">
                        <h3>Дялове след разходите</h3>
                        @foreach($projectRow['shares'] as $account => $amount)
                            <div class="tt-finance__row">
                                <span>{{ $accounts[$account] }}</span>
                                <strong>{{ Euro::format($amount) }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Изравняване</x-slot>
                @forelse($transfers as $transfer)
                    <div class="tt-finance__row">
                        <span>{{ $accounts[$transfer['from']] }} → {{ $accounts[$transfer['to']] }}</span>
                        <strong>{{ Euro::format($transfer['cents']) }}</strong>
                        @if($project)
                            <x-filament::link :href="FinanceMovementResource::getUrl('create', [
                                'project' => $project->id,
                                'kind' => 'transfer',
                                'from' => $transfer['from'],
                                'to' => $transfer['to'],
                                'amount' => Euro::decimal($transfer['cents']),
                            ])">Запиши превод</x-filament::link>
                        @endif
                    </div>
                @empty
                    <p class="tt-finance__muted">Няма нужда от превод.</p>
                @endforelse

                @if(! $project)
                    <div class="tt-finance__actions">{{ $this->settlePeriodAction }}</div>
                @elseif($projectRow['settlements'])
                    <div class="tt-finance__summary">
                        <h3>Общи изравнявания с този проект</h3>
                        @foreach($projectRow['settlements'] as $movement)
                            <x-filament::link :href="FinanceMovementResource::getUrl('view', ['record' => $movement])">Запис №{{ $movement }}</x-filament::link>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>

        @if(! $project)
            <div class="tt-finance__actions">
                <x-filament::link :href="$studioUrl">Общи разходи и дялове</x-filament::link>
            </div>
        @endif
    </div>
</x-filament-panels::page>

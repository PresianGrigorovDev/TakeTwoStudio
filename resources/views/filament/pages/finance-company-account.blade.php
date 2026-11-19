@php
    use App\Filament\Resources\FinanceMovementResource;
    use App\Models\FinanceMovement;
    use App\Support\Finance\Euro;
@endphp

<x-filament-panels::page>
    <div class="tt-finance tt-finance__stack">
        <x-filament::section>
            <form wire:submit="applyFilters" class="tt-finance__filters">
                {{ $this->form }}
                <x-filament::button type="submit" color="gray" wire:target="applyFilters">Покажи</x-filament::button>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Наличност и движения</x-slot>
            <dl class="tt-finance__metrics">
                <div><dt>Начално салдо за периода</dt><dd>{{ Euro::format($data['opening']) }}</dd></div>
                <div><dt>Постъпили</dt><dd>{{ Euro::format($data['incoming']) }}</dd></div>
                <div><dt>Излезли</dt><dd>{{ Euro::format($data['outgoing']) }}</dd></div>
                <div><dt>Налично към {{ \Illuminate\Support\Carbon::parse($through)->format('d.m.Y') }}</dt><dd>{{ Euro::format($data['balance']) }}</dd></div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Движения в избрания период</x-slot>
            @forelse($data['rows'] as $row)
                @php($movement = $row['movement'])
                <div class="tt-finance__row">
                    <div>
                        <x-filament::link :href="FinanceMovementResource::getUrl('view', ['record' => $movement])">
                            {{ FinanceMovement::KINDS[$movement->kind] }} · {{ $movement->project?->title ?? 'Общо за периода' }}
                        </x-filament::link>
                        <p class="tt-finance__muted">
                            {{ $movement->occurred_on->format('d.m.Y') }} · Въвел {{ $movement->recorded_by_name }}
                            @if($movement->method) · {{ FinanceMovement::METHODS[$movement->method] }} @endif
                        </p>
                        @if($movement->fromAccount || $movement->toAccount)
                            <p class="tt-finance__muted">
                                {{ $movement->fromAccount?->display_name ?? 'Клиент' }} → {{ $movement->toAccount?->display_name ?? 'Получател' }}
                            </p>
                        @endif
                    </div>
                    <strong>
                        @if($row['incoming']) +{{ Euro::format($row['incoming']) }} @endif
                        @if($row['outgoing']) −{{ Euro::format($row['outgoing']) }} @endif
                        @if(! $row['incoming'] && ! $row['outgoing']) Вътре в сметката @endif
                    </strong>
                </div>
            @empty
                <p class="tt-finance__muted">Няма записани движения за периода.</p>
            @endforelse
            @if($data['rows']->hasPages())
                <x-filament::pagination :paginator="$data['rows']" />
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">За внасяне във фирмената сметка</x-slot>
            <x-slot name="description">Оставащи суми по проекти към избраната крайна дата.</x-slot>

            @forelse($data['pending'] as $transfer)
                <div class="tt-finance__row">
                    <span>{{ $names[$transfer['from']] }} · {{ $data['projectNames'][$transfer['project_id']] }}</span>
                    <strong>{{ Euro::format($transfer['cents']) }}</strong>
                    <x-filament::link :href="FinanceMovementResource::getUrl('create', [
                        'project' => $transfer['project_id'],
                        'kind' => FinanceMovement::TRANSFER,
                        'from' => $transfer['from'],
                        'to' => $transfer['to'],
                        'amount' => Euro::decimal($transfer['cents']),
                    ])">Запиши вноска</x-filament::link>
                </div>
            @empty
                <p class="tt-finance__muted">Няма суми за внасяне.</p>
            @endforelse
            @if($data['receivable'])
                <div class="tt-finance__row tt-finance__summary">
                    <span>Общо за внасяне</span>
                    <strong>{{ Euro::format($data['receivable']) }}</strong>
                </div>
            @endif
            @if($data['payable'])
                <div class="tt-finance__row tt-finance__summary">
                    <span>Дължимо от фирмената сметка по проекти</span>
                    <strong>{{ Euro::format($data['payable']) }}</strong>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>

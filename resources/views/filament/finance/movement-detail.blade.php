@php
    $movement = $getRecord();
    $kinds = \App\Models\FinanceMovement::KINDS;
    $stages = \App\Models\FinanceMovement::STAGES;
    $methods = \App\Models\FinanceMovement::METHODS;
    $companyRate = (int) ($movement->details['company_reserve_bps'] ?? 0);
    $settlement = $movement->kind === \App\Models\FinanceMovement::SETTLEMENT;
    $accounts = $settlement ? \App\Models\FinanceAccount::choices(includeLegacy: true) : [];
@endphp

<x-filament::section>
    <x-slot name="heading">{{ $kinds[$movement->kind] }}</x-slot>
    <x-slot name="description">{{ $movement->occurred_on->format('d.m.Y') }} · Въвел {{ $movement->recorded_by_name }}</x-slot>

    <div class="tt-finance">
        <p class="tt-finance__amount">{{ \App\Support\Finance\Euro::format($movement->amount_cents) }}</p>

        @if($movement->voided_at)
            <p class="tt-finance__notice">Анулирано на {{ $movement->voided_at->format('d.m.Y') }} · {{ $movement->void_reason }}</p>
        @endif

        @if($movement->method)
            <p>{{ $methods[$movement->method] }}</p>
        @endif
        @if($movement->payment_stage)
            <p>{{ $stages[$movement->payment_stage] }}</p>
        @endif
        @if($companyRate > 0)
            <div class="tt-finance__row tt-finance__summary">
                <span>За фирмената сметка · {{ \App\Support\Finance\Euro::decimal($companyRate, ',') }}%</span>
                <strong>{{ \App\Support\Finance\Euro::format(\App\Support\Finance\CompanyReserve::cents($movement->amount_cents, $companyRate)) }}</strong>
            </div>
        @endif
        @if($movement->fromAccount)
            <p>Платил · {{ $movement->fromAccount->display_name }}</p>
        @endif
        @if($movement->toAccount)
            <p>Получил · {{ $movement->toAccount->display_name }}</p>
        @endif
        @if($movement->handler)
            <p>Извършил · {{ $movement->handler->display_name }}</p>
        @endif
        @if($movement->description)
            <p class="tt-finance__note">{{ $movement->description }}</p>
        @endif

        @if($settlement)
            @include('filament.finance.transfers', ['transfers' => $movement->details['transfers']])
            <p class="tt-finance__muted">Обхванати проекти и общи разходи · {{ count($movement->details['adjustments']) }}</p>
        @endif
    </div>
</x-filament::section>

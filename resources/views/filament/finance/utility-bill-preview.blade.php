<div class="tt-finance" role="status" aria-live="polite">
    @if ($payerName)
        <p class="tt-finance__muted">Платил · {{ $payerName }}</p>
    @endif
    @if ($payerName && $shares !== null)
        @forelse (array_filter(\Illuminate\Support\Arr::except($shares, [$payer])) as $account => $cents)
            <div class="tt-finance__row">
                <span>{{ $accounts[$account] }} → {{ $payerName }}</span>
                <strong>{{ \App\Support\Finance\Euro::format($cents) }}</strong>
            </div>
        @empty
            <p>Няма суми за възстановяване.</p>
        @endforelse
    @else
        <p class="tt-finance__muted">Попълнете сумата, платилия и дяловете, за да видите разпределението.</p>
    @endif
</div>

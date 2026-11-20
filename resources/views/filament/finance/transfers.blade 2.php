@forelse($transfers as $transfer)
    <div class="tt-finance__row">
        <span>{{ $accounts[$transfer['from']] }} → {{ $accounts[$transfer['to']] }}</span>
        <strong>{{ \App\Support\Finance\Euro::format($transfer['cents']) }}</strong>
    </div>
@empty
    <p class="tt-finance__muted">Салдата се прихващат без превод на пари.</p>
@endforelse

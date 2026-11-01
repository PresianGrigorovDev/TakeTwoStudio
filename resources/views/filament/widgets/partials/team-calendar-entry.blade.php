@php
    $colors = collect($entry['people'])->pluck('color');
    // One person: their colour; several: a striped bar with every colour.
    $bar = $colors->count() > 1
        ? 'linear-gradient(to bottom, ' . $colors->map(fn ($c, $i) => $c . ' ' . round($i * 100 / $colors->count()) . '% ' . round(($i + 1) * 100 / $colors->count()) . '%')->implode(', ') . ')'
        : ($colors->first() ?? '#94a3b8');
@endphp
<a href="{{ $entry['url'] }}"
   @class(['tt-cal__entry', 'is-' . $entry['kind'], 'is-pending' => $entry['pending']])
   style="--tt-bar: {{ $bar }}"
   title="{{ $entry['time'] }} · {{ $entry['title'] }}{{ $entry['location'] ? ' · ' . $entry['location'] : '' }}{{ $entry['people'] ? ' · ' . collect($entry['people'])->pluck('name')->implode(', ') : '' }}">
    <span class="tt-cal__time">@if($entry['kind'] === 'session')<span aria-label="фотосесия">📷</span> @endif{{ $entry['time'] }}@if($entry['multiDay']) <span aria-label="няколко дни">↔</span>@endif</span>
    <span class="tt-cal__name">{{ $entry['title'] }}</span>
    @if($entry['location'])
        <span class="tt-cal__location">📍 {{ $entry['location'] }}</span>
    @endif
    @if($entry['people'])
        <span class="tt-cal__people">
            @foreach($entry['people'] as $person)
                <span class="tt-cal__person" style="--tt-person: {{ $person['color'] }}">{{ \Illuminate\Support\Str::of($person['name'])->explode(' ')->first() }}</span>
            @endforeach
        </span>
    @elseif(in_array($entry['kind'], \App\Models\Booking::CLIENT_KINDS, true))
        <span class="tt-cal__people"><span class="tt-cal__person is-unassigned">неразпределен</span></span>
    @endif
</a>

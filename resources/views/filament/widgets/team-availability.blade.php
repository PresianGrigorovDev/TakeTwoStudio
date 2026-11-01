<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Кой е свободен</x-slot>
        <x-slot name="description">Изберете ден и часове – виждате кой е свободен и къде са заетите.</x-slot>

        <div class="tt-free">
            <div class="tt-free__filters">
                <label>
                    <span>Ден</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="date" />
                    </x-filament::input.wrapper>
                </label>
                <label>
                    <span>От</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="time" wire:model.live.debounce.500ms="from" />
                    </x-filament::input.wrapper>
                </label>
                <label>
                    <span>До</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="time" wire:model.live.debounce.500ms="to" />
                    </x-filament::input.wrapper>
                </label>
            </div>

            @if ($invalid)
                <p class="tt-free__note">Изберете ден и час „До“ след часа „От“.</p>
            @else
                @foreach ($closed as $entry)
                    <p class="tt-free__closed">Студиото е затворено {{ $entry->start_time }}–{{ $entry->end_time }}: {{ $entry->name }}</p>
                @endforeach

                <ul class="tt-free__list" wire:loading.class="is-loading">
                    @foreach ($people as $person)
                        <li class="tt-free__person">
                            <span class="tt-free__name"><span class="tt-cal__dot" style="background: {{ $person['color'] }}"></span>{{ $person['name'] }}</span>

                            @if ($person['free'])
                                <span class="tt-free__status is-free">Свободен</span>
                                <x-filament::link :href="$person['createUrl']" icon="heroicon-m-plus" size="sm">Запиши</x-filament::link>
                            @else
                                <span class="tt-free__status is-busy">Зает</span>
                                <span class="tt-free__busy">
                                    @foreach ($person['busy'] as $busy)
                                        <span>
                                            <a href="{{ $busy['url'] }}"><strong>{{ $busy['time'] }}</strong> {{ $busy['title'] }}</a>
                                            <small>({{ $busy['kind'] }})</small>
                                            @if ($busy['mapsUrl'])
                                                · <a href="{{ $busy['mapsUrl'] }}" target="_blank" rel="noopener">📍 {{ $busy['location'] }}</a>
                                            @endif
                                        </span>
                                    @endforeach
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

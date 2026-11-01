<x-filament-widgets::widget>
    <x-filament::section>
        <div class="tt-cal">
            {{-- Toolbar: month navigation, person filter, new entry --}}
            <div class="tt-cal__toolbar">
                <div class="tt-cal__nav">
                    <x-filament::icon-button icon="heroicon-m-chevron-left" color="gray" wire:click="previousMonth" label="Предишен месец" />
                    <h2 class="tt-cal__title">{{ $title }}</h2>
                    <x-filament::icon-button icon="heroicon-m-chevron-right" color="gray" wire:click="nextMonth" label="Следващ месец" />
                    <x-filament::button size="xs" color="gray" wire:click="currentMonth">Днес</x-filament::button>
                </div>
                <x-filament::button size="sm" icon="heroicon-m-plus" tag="a" :href="$createUrl">Нов запис</x-filament::button>
            </div>

            {{-- Legend = person filter --}}
            <div class="tt-cal__legend" role="group" aria-label="Филтър по човек">
                <button type="button" wire:click="filterMember(null)" @class(['tt-cal__chip', 'is-active' => $member === null])>Всички</button>
                @foreach($members as $person)
                    <button type="button" wire:click="filterMember({{ $person->id }})" @class(['tt-cal__chip', 'is-active' => $member === $person->id])>
                        <span class="tt-cal__dot" style="background: {{ $person->calendar_color_or_default }}"></span>{{ $person->name }}
                    </button>
                @endforeach
                <span class="tt-cal__hint"><span class="tt-cal__swatch is-session"></span>📷 фотосесия</span>
                <span class="tt-cal__hint"><span class="tt-cal__swatch is-pending"></span>чакаща заявка</span>
                <span class="tt-cal__hint"><span class="tt-cal__swatch is-busy"></span>лично зает</span>
                <span class="tt-cal__hint"><span class="tt-cal__swatch is-closed"></span>студиото е затворено</span>
            </div>

            {{-- Month grid (tablet / desktop) --}}
            <div class="tt-cal__grid" wire:loading.class="is-loading">
                @foreach($weekdays as $weekday)
                    <div class="tt-cal__weekday">{{ $weekday }}</div>
                @endforeach

                @foreach($weeks as $week)
                    @foreach($week as $day)
                        <div @class(['tt-cal__day', 'is-other-month' => ! $day['inMonth'], 'is-today' => $day['isToday'], 'is-past' => $day['isPast']])>
                            <a href="{{ $day['createUrl'] }}" class="tt-cal__daynum" title="Нов запис за {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d.m.Y') }}">
                                <span>{{ $day['day'] }}</span><span class="tt-cal__add" aria-hidden="true">+</span>
                            </a>
                            @foreach($day['entries'] as $entry)
                                @include('filament.widgets.partials.team-calendar-entry', ['entry' => $entry])
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- Agenda list (phones) --}}
            <div class="tt-cal__agenda">
                @forelse($agenda as $day)
                    <div @class(['tt-cal__agenda-day', 'is-today' => $day['isToday']])>
                        <a href="{{ $day['createUrl'] }}" class="tt-cal__agenda-date">
                            {{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('D, d F') }} <span aria-hidden="true">+</span>
                        </a>
                        @foreach($day['entries'] as $entry)
                            @include('filament.widgets.partials.team-calendar-entry', ['entry' => $entry])
                        @endforeach
                    </div>
                @empty
                    <p class="tt-cal__empty">Няма записи през този месец.</p>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

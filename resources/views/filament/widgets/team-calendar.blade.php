<x-filament-widgets::widget>
    <x-filament::section>
        <div class="tt-cal"
             x-on:calendar-booking-selected.window="$nextTick(() => {
                 const booking = Array.from($el.querySelectorAll('.tt-cal__entry.is-selected')).find(entry => entry.offsetWidth > 0);
                 booking?.focus({ preventScroll: true });
                 booking?.scrollIntoView({ block: 'center' });
             })">
            {{-- Toolbar: month navigation, person filter, new entry --}}
            <div class="tt-cal__toolbar">
                <div class="tt-cal__nav">
                    <h2 class="sr-only" aria-live="polite">{{ $title }}</h2>
                    <div class="tt-cal__period">
                        <x-filament::input.wrapper>
                            <x-filament::input.select aria-label="Месец" wire:change="selectPeriod($event.target.value)">
                                @foreach($monthNames as $index => $name)
                                    <option value="{{ $index + 1 }}" @selected($month === $index + 1)>{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper :valid="! $errors->has('year')">
                            <x-filament::input
                                wire:key="calendar-year-{{ $year }}-{{ $month }}"
                                type="number"
                                inputmode="numeric"
                                aria-label="Година"
                                :aria-invalid="$errors->has('year') ? 'true' : 'false'"
                                :min="$this::MIN_YEAR"
                                :max="$this::MAX_YEAR"
                                :value="$year"
                                wire:change="selectPeriod(null, $event.target.value)"
                                x-on:keydown.enter.prevent="$el.blur()"
                            />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="tt-cal__nav-actions">
                        <x-filament::icon-button icon="heroicon-m-chevron-left" color="gray" wire:click="previousMonth" label="Предишен месец" :disabled="$year === $this::MIN_YEAR && $month === 1" />
                        <x-filament::icon-button icon="heroicon-m-chevron-right" color="gray" wire:click="nextMonth" label="Следващ месец" :disabled="$year === $this::MAX_YEAR && $month === 12" />
                        <x-filament::button size="xs" color="gray" wire:click="currentMonth">Днес</x-filament::button>
                    </div>
                </div>
                <x-filament::button size="sm" icon="heroicon-m-plus" tag="a" :href="$createUrl">Нов запис</x-filament::button>
            </div>
            @error('year')
                <p class="tt-cal__period-error" role="alert">{{ $message }}</p>
            @enderror

            <div class="tt-cal__search" role="search" aria-label="Календар" wire:keydown.escape="clearSearch">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Клиент, телефон или локация"
                        aria-label="Търсене по клиент, телефон или локация"
                        autocomplete="off"
                        maxlength="100"
                    />
                </x-filament::input.wrapper>
                <p class="sr-only" role="status" wire:loading wire:target="search">Търсене…</p>
                @if($hasSearch)
                    @if($searchResults->isEmpty())
                        <p class="tt-cal__empty" role="status">Няма намерени ангажименти.</p>
                    @else
                        <ul class="tt-cal__results" aria-label="Намерени ангажименти">
                            @foreach($searchResults as $result)
                                <li wire:key="calendar-search-{{ $result->id }}">
                                    <button type="button" class="tt-cal__result" wire:click="selectBooking({{ $result->id }})">
                                        <strong>{{ $result->name }}</strong>
                                        <span>{{ implode(' · ', array_filter([$result->event_date->format('d.m.Y'), $result->start_time, $result->phone, $result->location])) }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>

            <div class="tt-cal__filters">
                <div class="tt-cal__chips" role="group" aria-label="Филтър по човек">
                    <button type="button" wire:click="filterMember(null)" class="tt-cal__chip" aria-pressed="{{ $member === null && ! $unassignedOnly ? 'true' : 'false' }}">Всички</button>
                    @foreach($members as $person)
                        <button type="button" wire:click="filterMember({{ $person->id }})" class="tt-cal__chip" aria-pressed="{{ $member === $person->id ? 'true' : 'false' }}">
                            <span class="tt-cal__dot" style="background: {{ $person->calendar_color_or_default }}"></span>{{ $person->name }}
                        </button>
                    @endforeach
                </div>
                <div class="tt-cal__chips" role="group" aria-label="Филтър за ангажименти">
                    <button type="button" wire:click="togglePending" class="tt-cal__chip" aria-pressed="{{ $pendingOnly ? 'true' : 'false' }}">Чакащи</button>
                    <button type="button" wire:click="toggleUnassigned" class="tt-cal__chip" aria-pressed="{{ $unassignedOnly ? 'true' : 'false' }}">Без назначен екип</button>
                </div>
            </div>

            <div class="tt-cal__legend" aria-label="Легенда">
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
                            <div class="tt-cal__day-actions">
                                <button type="button" class="tt-cal__daynum" aria-pressed="{{ $availabilityDate === $day['date'] ? 'true' : 'false' }}"
                                        wire:click="checkAvailability('{{ $day['date'] }}')" aria-label="Кой е свободен на {{ $day['label'] }}" title="Кой е свободен на {{ $day['label'] }}">
                                    <span>{{ $day['day'] }}</span>
                                </button>
                                <a href="{{ $day['createUrl'] }}" class="tt-cal__add" aria-label="Нов запис за {{ $day['label'] }}" title="Нов запис за {{ $day['label'] }}">+</a>
                            </div>
                            @foreach($day['entries'] as $entry)
                                @include('filament.widgets.partials.team-calendar-entry', ['entry' => $entry])
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- Agenda list (phones) --}}
            <div class="tt-cal__agenda">
                <div class="tt-cal__picker" role="group" aria-labelledby="tt-cal-availability-{{ $this->getId() }}">
                    <p id="tt-cal-availability-{{ $this->getId() }}">Кой е свободен?</p>
                    @foreach($weekdays as $weekday)
                        <span class="tt-cal__picker-weekday" aria-hidden="true">{{ $weekday }}</span>
                    @endforeach
                    @foreach($weeks as $week)
                        @foreach($week as $day)
                            @if($day['inMonth'])
                                <button type="button" @class(['tt-cal__picker-date', 'is-today' => $day['isToday']]) aria-pressed="{{ $availabilityDate === $day['date'] ? 'true' : 'false' }}"
                                        wire:click="checkAvailability('{{ $day['date'] }}')" aria-label="Кой е свободен на {{ $day['label'] }}">{{ $day['day'] }}</button>
                            @else
                                <span aria-hidden="true"></span>
                            @endif
                        @endforeach
                    @endforeach
                </div>
                @forelse($agenda as $day)
                    <div @class(['tt-cal__agenda-day', 'is-today' => $day['isToday']])>
                        <div class="tt-cal__day-actions">
                            <button type="button" class="tt-cal__agenda-date" wire:click="checkAvailability('{{ $day['date'] }}')"
                                    aria-label="Кой е свободен на {{ $day['label'] }}">{{ $day['agendaLabel'] }}</button>
                            <a href="{{ $day['createUrl'] }}" class="tt-cal__add" aria-label="Нов запис за {{ $day['label'] }}" title="Нов запис за {{ $day['label'] }}">+</a>
                        </div>
                        @foreach($day['entries'] as $entry)
                            @include('filament.widgets.partials.team-calendar-entry', ['entry' => $entry])
                        @endforeach
                    </div>
                @empty
                    <p class="tt-cal__empty">{{ $pendingOnly || $unassignedOnly || $member ? 'Няма записи с тези филтри.' : 'Няма записи през този месец.' }}</p>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

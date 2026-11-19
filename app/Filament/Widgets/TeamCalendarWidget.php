<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Calendar;
use App\Filament\Resources\BookingResource;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\TeamMember;
use App\Support\CalendarContext;
use App\Support\TeamCalendar;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

/**
 * Month calendar of the whole team on the admin dashboard: every engagement, personal
 * busy time and studio-closed day, coloured by person; several entries per day and
 * several people per entry. Pick a day to check availability or an entry to edit it.
 */
class TeamCalendarWidget extends Widget
{
    // A displayed month can include dates from its neighbouring years.
    public const MIN_YEAR = CalendarContext::MIN_YEAR;

    public const MAX_YEAR = CalendarContext::MAX_YEAR;

    protected static string $view = 'filament.widgets.team-calendar';

    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';

    public int $year;

    public int $month;

    /** Show only this person's entries (null = everybody). */
    public ?int $member = null;

    public bool $pendingOnly = false;

    public bool $unassignedOnly = false;

    public string $search = '';

    #[Locked]
    public ?int $selectedBooking = null;

    #[Locked]
    public ?string $availabilityDate = null;

    /** Where it is shown: 'dashboard' or 'page' (Работа → Календар); saving returns there. */
    #[Locked]
    public string $origin = 'dashboard';

    public function mount(): void
    {
        $context = CalendarContext::fromRequest();
        $now = now();
        $this->setPeriod($context['year'] ?? $now->year, $context['month'] ?? $now->month);
        $this->pendingOnly = isset($context['pending']);
        $this->unassignedOnly = isset($context['unassigned']);
        $this->availabilityDate = $context['availability'] ?? null;
        $member = $context['member'] ?? null;
        if (! $this->unassignedOnly && $member && TeamMember::whereKey($member)->where('is_active', true)->exists()) {
            $this->member = $member;
        }
    }

    public function previousMonth(): void
    {
        $this->shift(-1);
    }

    public function nextMonth(): void
    {
        $this->shift(1);
    }

    public function currentMonth(): void
    {
        $now = now();
        $this->setPeriod($now->year, $now->month);
    }

    public function selectPeriod(mixed $month = null, mixed $year = null): void
    {
        $period = validator([
            'month' => $month ?? $this->month,
            'year' => $year ?? $this->year,
        ], [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:'.self::MIN_YEAR.','.self::MAX_YEAR,
        ], [
            'month.*' => 'Изберете валиден месец.',
            'year.between' => 'Въведете година между :min и :max.',
            'year.required' => 'Въведете година.',
            'year.integer' => 'Въведете цяла година.',
        ])->validate();

        $this->setPeriod((int) $period['year'], (int) $period['month']);
    }

    public function filterMember(?int $memberId): void
    {
        $this->member = $this->member === $memberId ? null : $memberId;
        $this->unassignedOnly = false;
        $this->selectedBooking = null;
    }

    public function togglePending(): void
    {
        $this->pendingOnly = ! $this->pendingOnly;
        $this->selectedBooking = null;
    }

    public function toggleUnassigned(): void
    {
        $this->unassignedOnly = ! $this->unassignedOnly;
        if ($this->unassignedOnly) {
            $this->member = null;
        }
        $this->selectedBooking = null;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function selectBooking(int $id): void
    {
        $booking = Booking::active()->whereIn('kind', Booking::CLIENT_KINDS)->with('teamMembers')->find($id);
        if (! $booking || $booking->event_date->year < self::MIN_YEAR || $booking->event_date->year > self::MAX_YEAR) {
            return;
        }

        if ($this->member !== null && ! $booking->teamMembers->contains('id', $this->member)) {
            $this->member = null;
        }
        $this->pendingOnly = $this->pendingOnly && $booking->status === 'pending';
        $this->unassignedOnly = $this->unassignedOnly && $booking->teamMembers->isEmpty();
        $this->setPeriod($booking->event_date->year, $booking->event_date->month);
        $this->selectedBooking = $booking->id;
        $this->clearSearch();
        $this->dispatch('calendar-booking-selected');
    }

    public function checkAvailability(string $date): void
    {
        if (! CalendarContext::validDate($date)) {
            return;
        }

        $this->availabilityDate = $date;
        if ($this->origin !== 'page') {
            $this->redirect(Calendar::getUrl($this->calendarContext()).'#team-availability');

            return;
        }

        $this->dispatch('calendar-availability-selected', date: $date, context: $this->calendarContext())
            ->to(TeamAvailabilityWidget::class);
    }

    private function shift(int $months): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonths($months);
        if ($date->year < self::MIN_YEAR || $date->year > self::MAX_YEAR) {
            return;
        }

        $this->setPeriod($date->year, $date->month);
    }

    private function setPeriod(int $year, int $month): void
    {
        $this->year = $year;
        $this->month = $month;
        $this->selectedBooking = null;
        $this->resetValidation(['year', 'month']);
    }

    private function calendarContext(): array
    {
        return array_filter([
            'year' => $this->year,
            'month' => $this->month,
            'member' => $this->member,
            'pending' => $this->pendingOnly ? 1 : null,
            'unassigned' => $this->unassignedOnly ? 1 : null,
            'availability' => $this->availabilityDate,
        ]);
    }

    protected function getViewData(): array
    {
        $first = Carbon::create($this->year, $this->month, 1)->locale('bg');
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $entries = TeamCalendar::entries($gridStart->toDateString(), $gridEnd->toDateString(), $this->member);
        if ($this->pendingOnly) {
            $entries = $entries->where('status', 'pending')->whereIn('kind', Booking::CLIENT_KINDS);
        }
        if ($this->unassignedOnly) {
            $entries = $entries->filter(fn (Booking $entry) => Booking::isClientKind($entry->kind) && $entry->teamMembers->isEmpty());
        }

        $members = TeamMember::where('is_active', true)->orderBy('display_order')->get();
        $today = now()->toDateString();
        $fullDay = [sprintf('%02d:00', BookingController::getWorkStart()), sprintf('%02d:00', BookingController::getWorkEnd())];
        $from = $this->origin === 'page' ? 'calendar-page' : 'calendar';
        $return = ['from' => $from] + $this->calendarContext();

        $days = [];
        foreach (TeamCalendar::days($gridStart, $gridEnd) as $date) {
            $label = Carbon::parse($date)->locale('bg');
            $days[] = [
                'date' => $date,
                'day' => (int) substr($date, 8, 2),
                'label' => $label->translatedFormat('d F Y'),
                'agendaLabel' => $label->translatedFormat('D, d F'),
                'inMonth' => substr($date, 0, 7) === $first->format('Y-m'),
                'isToday' => $date === $today,
                'isPast' => $date < $today,
                'createUrl' => BookingResource::getUrl('create', ['date' => $date] + $return),
                'entries' => $entries->filter(fn (Booking $e) => $e->coversDate($date))->map(fn (Booking $e) => [
                    'kind' => $e->kind,
                    'title' => $e->name,
                    'location' => $e->location,
                    'time' => [$e->start_time, $e->end_time] === $fullDay ? 'цял ден' : $e->start_time.'–'.$e->end_time,
                    'pending' => $e->status === 'pending',
                    'selected' => $this->selectedBooking === $e->id && $date === $e->event_date->toDateString(),
                    'multiDay' => $e->end_date && $e->end_date->gt($e->event_date),
                    'people' => $e->teamMembers->map(fn (TeamMember $m) => ['name' => $m->name, 'color' => $m->calendar_color_or_default])->all(),
                    'url' => BookingResource::getUrl('edit', ['record' => $e->id] + $return),
                ])->values()->all(),
            ];
        }

        return [
            'title' => Str::ucfirst($first->translatedFormat('F Y')),
            'monthNames' => array_map(
                fn (int $month) => Str::ucfirst($first->copy()->month($month)->translatedFormat('F')),
                range(1, 12),
            ),
            'weeks' => array_chunk($days, 7),
            'agenda' => array_values(array_filter($days, fn ($d) => $d['inMonth'] && $d['entries'])),
            'members' => $members,
            'weekdays' => ['Пон', 'Вт', 'Ср', 'Чет', 'Пет', 'Съб', 'Нед'],
            'createUrl' => BookingResource::getUrl('create', $return),
            'searchResults' => TeamCalendar::searchBookings($this->search),
            'hasSearch' => mb_strlen(trim($this->search)) >= 2,
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\TeamMember;
use App\Support\TeamCalendar;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Month calendar of the whole team on the admin dashboard: every engagement, personal
 * busy time and studio-closed day, coloured by person; several entries per day and
 * several people per entry. Click a day to add an entry, an entry to edit it.
 */
class TeamCalendarWidget extends Widget
{
    // A displayed month can include dates from its neighbouring years.
    public const MIN_YEAR = 1001;

    public const MAX_YEAR = 9998;

    protected static string $view = 'filament.widgets.team-calendar';

    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';

    public int $year;

    public int $month;

    /** Show only this person's entries (null = everybody). */
    public ?int $member = null;

    /** Where it is shown: 'dashboard' or 'page' (Работа → Календар); saving returns there. */
    public string $origin = 'dashboard';

    public function mount(): void
    {
        $this->currentMonth();
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
        $this->resetValidation(['year', 'month']);
    }

    protected function getViewData(): array
    {
        $first = Carbon::create($this->year, $this->month, 1);
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $entries = TeamCalendar::entries($gridStart->toDateString(), $gridEnd->toDateString(), $this->member);
        $members = TeamMember::where('is_active', true)->orderBy('display_order')->get();
        $today = now()->toDateString();
        $fullDay = [sprintf('%02d:00', BookingController::getWorkStart()), sprintf('%02d:00', BookingController::getWorkEnd())];
        $from = $this->origin === 'page' ? 'calendar-page' : 'calendar';

        $days = [];
        foreach (TeamCalendar::days($gridStart, $gridEnd) as $date) {
            $days[] = [
                'date' => $date,
                'day' => (int) substr($date, 8, 2),
                'inMonth' => substr($date, 0, 7) === $first->format('Y-m'),
                'isToday' => $date === $today,
                'isPast' => $date < $today,
                'createUrl' => BookingResource::getUrl('create', array_filter(['date' => $date, 'member' => $this->member, 'from' => $from])),
                'entries' => $entries->filter(fn (Booking $e) => $e->coversDate($date))->map(fn (Booking $e) => [
                    'kind' => $e->kind,
                    'title' => $e->name,
                    'location' => $e->location,
                    'time' => [$e->start_time, $e->end_time] === $fullDay ? 'цял ден' : $e->start_time.'–'.$e->end_time,
                    'pending' => $e->status === 'pending',
                    'multiDay' => $e->end_date && $e->end_date->gt($e->event_date),
                    'people' => $e->teamMembers->map(fn (TeamMember $m) => ['name' => $m->name, 'color' => $m->calendar_color_or_default])->all(),
                    'url' => BookingResource::getUrl('edit', ['record' => $e->id, 'from' => $from]),
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
            'createUrl' => BookingResource::getUrl('create', array_filter(['member' => $this->member, 'from' => $from])),
        ];
    }
}

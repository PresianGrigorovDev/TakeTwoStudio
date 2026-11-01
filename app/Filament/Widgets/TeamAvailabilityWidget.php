<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\TeamMember;
use App\Support\TeamCalendar;
use Filament\Widgets\Widget;

/**
 * Работа → Календар, under the month view: pick a day and hours, see who is free
 * and where the busy ones are (time, place). Not shown on the dashboard.
 */
class TeamAvailabilityWidget extends Widget
{
    protected static string $view = 'filament.widgets.team-availability';

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public string $date = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->from = sprintf('%02d:00', BookingController::getWorkStart());
        $this->to = sprintf('%02d:00', BookingController::getWorkEnd());
    }

    protected function getViewData(): array
    {
        $from = substr($this->from, 0, 5);
        $to = substr($this->to, 0, 5);

        if (! $this->date || ! $from || ! $to || $from >= $to) {
            return ['invalid' => true, 'people' => [], 'closed' => collect()];
        }

        $members = TeamMember::where('is_active', true)->orderBy('display_order')->get();
        $taken = TeamCalendar::conflicts($this->date, null, $from, $to, $members->modelKeys());
        $closed = $taken->where('kind', Booking::KIND_CLOSED)->values();

        $people = $members->map(function (TeamMember $member) use ($taken, $closed, $from, $to) {
            $busy = $taken->filter(fn (Booking $e) => $e->kind !== Booking::KIND_CLOSED && $e->teamMembers->contains('id', $member->id))->values();

            return [
                'name' => $member->name,
                'color' => $member->calendar_color_or_default,
                'free' => $busy->isEmpty() && $closed->isEmpty(),
                'busy' => $busy->map(fn (Booking $e) => [
                    'time' => $e->start_time.'–'.$e->end_time,
                    'title' => $e->name,
                    'kind' => $e->kind_label,
                    'location' => $e->location,
                    'mapsUrl' => BookingResource::mapsUrl($e->location),
                    'url' => BookingResource::getUrl('edit', ['record' => $e->id, 'from' => 'calendar-page']),
                ])->all(),
                'createUrl' => BookingResource::getUrl('create', ['date' => $this->date, 'start' => $from, 'end' => $to, 'member' => $member->id, 'from' => 'calendar-page']),
            ];
        })->all();

        return ['invalid' => false, 'people' => $people, 'closed' => $closed];
    }
}

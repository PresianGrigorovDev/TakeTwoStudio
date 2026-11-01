<?php

namespace App\Support;

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\TeamMember;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Who is busy when, computed from the calendar entries (bookings):
 *  - client engagement / photo session: its people; nobody assigned yet still takes one person,
 *  - busy: its people (vacation, sick leave...),
 *  - closed: the whole studio.
 * Capacity = number of active team members (Filament → Екип), not a constant.
 * Used by the dashboard calendar, the conflict warning and the public /booking page.
 */
final class TeamCalendar
{
    public static function capacity(): int
    {
        return max(1, TeamMember::where('is_active', true)->count());
    }

    /** Active entries touching [$from, $to], with their people, in calendar order. */
    public static function entries(string $from, string $to, ?int $memberId = null): Collection
    {
        return Booking::active()
            ->overlappingDays($from, $to)
            ->when($memberId, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('teamMembers', fn ($q) => $q->whereKey($memberId))
                ->orWhere('kind', Booking::KIND_CLOSED)))
            ->with('teamMembers')
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Per working hour of $date: people busy, unassigned engagements, studio closed.
     *
     * @return array<string,array{members:array<int,int>,unassigned:int,closed:bool}>
     */
    public static function occupancy(string $date, Collection $entries): array
    {
        $hours = array_fill_keys(BookingController::getWorkingHours(), ['members' => [], 'unassigned' => 0, 'closed' => false]);

        foreach ($entries as $entry) {
            if (! $entry->coversDate($date)) {
                continue;
            }

            $memberIds = $entry->teamMembers->modelKeys();

            foreach ($entry->occupiedHours() as $hour) {
                if (! isset($hours[$hour])) {
                    continue;
                }
                if ($entry->kind === Booking::KIND_CLOSED) {
                    $hours[$hour]['closed'] = true;
                } elseif ($memberIds) {
                    $hours[$hour]['members'] = array_values(array_unique(array_merge($hours[$hour]['members'], $memberIds)));
                } elseif (Booking::isClientKind($entry->kind)) {
                    $hours[$hour]['unassigned']++;
                }
            }
        }

        return $hours;
    }

    /** Working hours of $date in which at least one person is still free. */
    public static function availableHours(string $date, ?Collection $entries = null): array
    {
        $entries ??= self::entries($date, $date);
        $capacity = self::capacity();

        return array_keys(array_filter(
            self::occupancy($date, $entries),
            fn (array $slot) => ! $slot['closed'] && count($slot['members']) + $slot['unassigned'] < $capacity
        ));
    }

    /** 'available' | 'partial' | 'unavailable' for the public month grid. */
    public static function dayStatus(string $date, Collection $entries): string
    {
        $capacity = self::capacity();
        $slots = self::occupancy($date, $entries);
        $free = 0;
        $touched = false;

        foreach ($slots as $slot) {
            $busy = count($slot['members']) + $slot['unassigned'];
            $touched = $touched || $slot['closed'] || $busy > 0;
            if (! $slot['closed'] && $busy < $capacity) {
                $free++;
            }
        }

        return $free === 0 ? 'unavailable' : ($touched ? 'partial' : 'available');
    }

    /**
     * Other active entries that would double-book one of $memberIds (or a closed studio)
     * between $start and $end on any day of [$date, $endDate].
     */
    public static function conflicts(string $date, ?string $endDate, string $start, string $end, array $memberIds, ?int $ignoreId = null): Collection
    {
        $endDate = ($endDate && $endDate > $date) ? $endDate : $date;
        $memberIds = array_map('intval', array_filter($memberIds));

        return Booking::active()
            ->overlappingDays($date, $endDate)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->where(fn ($q) => $q->where('kind', Booking::KIND_CLOSED)
                ->orWhereHas('teamMembers', fn ($q) => $q->whereIn('team_members.id', $memberIds ?: [0])))
            ->with('teamMembers')
            ->orderBy('event_date')
            ->get();
    }

    /** "Симеон – Сватба (12.11, 10:00–14:00)" lines for warnings. */
    public static function describeConflicts(Collection $conflicts, array $memberIds): array
    {
        return $conflicts->map(function (Booking $entry) use ($memberIds) {
            $who = $entry->kind === Booking::KIND_CLOSED
                ? 'Студиото е затворено'
                : $entry->teamMembers->whereIn('id', array_map('intval', $memberIds))->pluck('name')->implode(', ');

            return sprintf('%s – %s (%s, %s–%s)', $who, $entry->name, $entry->event_date->format('d.m'), $entry->start_time, $entry->end_time);
        })->all();
    }

    /** Days of [$from, $to] as 'Y-m-d' strings. */
    public static function days(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $days[] = $d->toDateString();
        }

        return $days;
    }
}

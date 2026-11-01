<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Блокирани дати" (one row per day, whole studio) move into the team calendar as
 * "studio closed" entries, so /booking behaves exactly as before. Several entries
 * per day are possible from now on. Each contiguous run of blocked hours becomes
 * one entry; a day without hours is blocked within the working day.
 * The blocked_dates table itself is kept untouched.
 */
return new class extends Migration
{
    private const NOTE = 'Пренесено от „Блокирани дати“';

    private const WORK_START = '08:00';

    private const WORK_END = '21:00';

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('blocked_dates')) {
            return;
        }

        foreach (DB::table('blocked_dates')->orderBy('date')->get() as $blocked) {
            $hours = json_decode((string) $blocked->blocked_hours, true) ?: [];
            $ranges = $hours ? $this->ranges($hours) : [[self::WORK_START, self::WORK_END]];

            foreach ($ranges as [$start, $end]) {
                DB::table('bookings')->insert([
                    'kind' => 'closed',
                    'name' => trim((string) $blocked->reason) ?: 'Блокиран ден',
                    'event_date' => substr((string) $blocked->date, 0, 10),
                    'start_time' => $start,
                    'end_time' => $end,
                    'service_type' => null,
                    'status' => 'confirmed',
                    'admin_notes' => self::NOTE,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('bookings')->where('kind', 'closed')->where('admin_notes', self::NOTE)->delete();
    }

    /**
     * ["09:00","10:00","14:00"] -> [["09:00","11:00"],["14:00","15:00"]]
     *
     * @return array<int,array{0:string,1:string}>
     */
    private function ranges(array $hours): array
    {
        $values = collect($hours)->map(fn ($h) => (int) substr((string) $h, 0, 2))->unique()->sort()->values();
        $ranges = [];
        $start = $prev = null;

        foreach ($values as $hour) {
            if ($start === null) {
                $start = $prev = $hour;
            } elseif ($hour === $prev + 1) {
                $prev = $hour;
            } else {
                $ranges[] = [$start, $prev + 1];
                $start = $prev = $hour;
            }
        }
        if ($start !== null) {
            $ranges[] = [$start, $prev + 1];
        }

        return array_map(fn ($r) => [sprintf('%02d:00', $r[0]), sprintf('%02d:00', $r[1])], $ranges);
    }
};

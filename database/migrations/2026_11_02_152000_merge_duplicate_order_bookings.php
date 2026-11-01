<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Запиши в календара" on an order used to create one booking per assigned person.
 * Now one entry holds several people, so identical copies of the same order slot
 * are merged into the oldest row and their people moved onto it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $groups = DB::table('bookings')
            ->whereNotNull('order_id')
            ->get(['id', 'order_id', 'event_date', 'start_time', 'end_time', 'status'])
            ->groupBy(fn ($b) => implode('|', [$b->order_id, $b->event_date, $b->start_time, $b->end_time, $b->status]))
            ->filter(fn ($group) => $group->count() > 1);

        foreach ($groups as $group) {
            $keepId = $group->min('id');
            $dropIds = $group->pluck('id')->reject(fn ($id) => $id === $keepId)->values();

            foreach (DB::table('booking_team_member')->whereIn('booking_id', $dropIds)->pluck('team_member_id') as $memberId) {
                DB::table('booking_team_member')->insertOrIgnore([
                    'booking_id' => $keepId,
                    'team_member_id' => $memberId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('bookings')->whereIn('id', $dropIds)->delete();
        }
    }

    public function down(): void
    {
        // Merged copies were identical apart from the person; nothing to restore.
    }
};

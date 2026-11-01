<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A calendar entry can have several people (e.g. two photographers at one wedding).
 * Replaces bookings.team_member_id with a pivot, like orders -> order_team_member.
 *
 * Safe to re-run: on production (2026-11-02) the first attempt created the pivot and
 * then failed because bookings.team_member_id had no foreign key to drop. MySQL DDL is
 * not transactional, so every step checks what already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_team_member')) {
            Schema::create('booking_team_member', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['booking_id', 'team_member_id']);
            });
        }

        if (! Schema::hasColumn('bookings', 'team_member_id')) {
            return;
        }

        foreach (DB::table('bookings')->whereNotNull('team_member_id')->get(['id', 'team_member_id']) as $booking) {
            DB::table('booking_team_member')->insertOrIgnore([
                'booking_id' => $booking->id,
                'team_member_id' => $booking->team_member_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop the foreign key and index only if they really exist, then the column.
        $foreignKeys = collect(Schema::getForeignKeys('bookings'))
            ->filter(fn (array $fk) => $fk['columns'] === ['team_member_id'])
            ->pluck('name');
        $indexes = collect(Schema::getIndexes('bookings'))
            ->filter(fn (array $index) => $index['columns'] === ['team_member_id'] && ! $index['primary'])
            ->pluck('name');

        Schema::table('bookings', function (Blueprint $table) use ($foreignKeys) {
            foreach ($foreignKeys as $name) {
                $table->dropForeign($name);
            }
        });

        Schema::table('bookings', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $name) {
                $table->dropIndex($name);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('team_member_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bookings', 'team_member_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('team_member_id')->nullable()->after('order_id')
                    ->constrained('team_members')->nullOnDelete();
            });
        }

        if (Schema::hasTable('booking_team_member')) {
            foreach (DB::table('booking_team_member')->orderBy('id')->get() as $row) {
                DB::table('bookings')->where('id', $row->booking_id)->whereNull('team_member_id')
                    ->update(['team_member_id' => $row->team_member_id]);
            }
        }

        Schema::dropIfExists('booking_team_member');
    }
};

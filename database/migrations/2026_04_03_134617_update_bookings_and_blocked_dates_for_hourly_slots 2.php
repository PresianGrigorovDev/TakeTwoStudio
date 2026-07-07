<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('time_slot');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('start_time', 5)->after('event_date'); // e.g. "09:00"
            $table->string('end_time', 5)->after('start_time');   // e.g. "12:00"
        });

        Schema::table('blocked_dates', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        Schema::table('blocked_dates', function (Blueprint $table) {
            $table->json('blocked_hours')->nullable()->after('date'); // e.g. ["09:00","10:00"] or null = whole day
            $table->string('reason')->nullable()->after('blocked_hours');
        });

        // Remove unique constraint on date (now we keep one row per date, blocked_hours handles granularity)
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
            $table->enum('time_slot', ['morning', 'afternoon', 'full_day'])->default('full_day');
        });

        Schema::table('blocked_dates', function (Blueprint $table) {
            $table->dropColumn('blocked_hours');
        });
    }
};

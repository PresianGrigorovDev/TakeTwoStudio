<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A calendar entry can have several people (e.g. two photographers at one wedding).
 * Replaces bookings.team_member_id with a pivot, like orders -> order_team_member.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_team_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['booking_id', 'team_member_id']);
        });

        foreach (DB::table('bookings')->whereNotNull('team_member_id')->get(['id', 'team_member_id']) as $booking) {
            DB::table('booking_team_member')->insert([
                'booking_id' => $booking->id,
                'team_member_id' => $booking->team_member_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_member_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('team_member_id')->nullable()->after('order_id')
                ->constrained('team_members')->nullOnDelete();
        });

        foreach (DB::table('booking_team_member')->orderBy('id')->get() as $row) {
            DB::table('bookings')->where('id', $row->booking_id)->whereNull('team_member_id')
                ->update(['team_member_id' => $row->team_member_id]);
        }

        Schema::dropIfExists('booking_team_member');
    }
};

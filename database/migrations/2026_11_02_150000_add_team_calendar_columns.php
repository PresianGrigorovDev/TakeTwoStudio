<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Internal team calendar (Filament dashboard): every booking row becomes a calendar
 * entry of a kind - client engagement, personal "busy" (vacation, sick leave...) or
 * studio closed - optionally spanning several days. Team members get a colour.
 */
return new class extends Migration
{
    /** Distinct, readable on white and dark backgrounds. */
    private const PALETTE = ['#2563eb', '#db2777', '#059669', '#d97706', '#7c3aed', '#0891b2', '#dc2626', '#4d7c0f'];

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('kind', 20)->default('client')->after('id');
            $table->date('end_date')->nullable()->after('event_date');
            $table->index('kind');
        });

        // Internal entries (vacation, studio closed) have no client phone or service.
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
            $table->string('service_type')->nullable()->change();
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->string('calendar_color', 7)->nullable()->after('career_start_year');
        });

        foreach (DB::table('team_members')->orderBy('display_order')->orderBy('id')->pluck('id')->values() as $i => $id) {
            DB::table('team_members')->where('id', $id)->whereNull('calendar_color')
                ->update(['calendar_color' => self::PALETTE[$i % count(self::PALETTE)]]);
        }
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('calendar_color');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'end_date']);
        });
    }
};

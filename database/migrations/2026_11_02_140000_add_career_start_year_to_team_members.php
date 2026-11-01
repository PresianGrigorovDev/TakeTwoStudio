<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Year each team member started working professionally ("в професията от 2013 г."):
 * a concrete, verifiable experience fact for /za-nas, the home team modal and llms.txt,
 * editable in Filament (Екип) instead of "над 10 години" typed into texts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->unsignedSmallInteger('career_start_year')->nullable()->after('role_en');
        });

        // Confirmed by the owner on 2026-11-02; only fills empty rows.
        foreach (['Пресиан%' => 2013, 'Симеон%' => 2015] as $name => $year) {
            DB::table('team_members')->where('name', 'like', $name)->whereNull('career_start_year')->update(['career_start_year' => $year]);
        }
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('career_start_year');
        });
    }
};

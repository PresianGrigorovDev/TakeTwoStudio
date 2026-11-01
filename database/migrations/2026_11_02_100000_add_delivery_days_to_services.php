<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Maximum delivery time per service (working days), editable in Filament
 * (Услуги). Shown in llms.txt and available to pages/FAQs, so the number
 * lives in one place instead of being typed into several texts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_days')->nullable()->after('description_bg');
        });

        // Initial values confirmed by the owner on 2026-11-02; only fills empty rows.
        foreach (['proms' => 70, 'graduation' => 20, 'weddings' => 100, 'baptism' => 30] as $slug => $days) {
            DB::table('services')->where('slug', $slug)->whereNull('delivery_days')->update(['delivery_days' => $days]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('delivery_days');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Opening hours as site settings (Settings::opensAt()/closesAt()) instead of
 * text typed into the schema graph, /kontakti and llms.txt.
 */
return new class extends Migration
{
    private const ROWS = [
        ['setting_key' => 'site_hours_opens', 'setting_value' => '09:00', 'description' => 'Работно време: отваря (всеки ден), формат ЧЧ:ММ'],
        ['setting_key' => 'site_hours_closes', 'setting_value' => '18:00', 'description' => 'Работно време: затваря (всеки ден), формат ЧЧ:ММ'],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $row) {
            if (! DB::table('site_settings')->where('setting_key', $row['setting_key'])->exists()) {
                DB::table('site_settings')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        }

        \App\Support\Settings::forget();
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('setting_key', array_column(self::ROWS, 'setting_key'))->delete();

        \App\Support\Settings::forget();
    }
};

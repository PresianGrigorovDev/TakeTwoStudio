<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * YouTube channel and Google Business Profile URLs given by the owner on 2026-11-02.
 * They feed schema.org sameAs, the footer/contact links and llms.txt via Settings::socialLinks().
 * Fills only missing or empty values, so anything already set in Filament wins.
 */
return new class extends Migration
{
    private const VALUES = [
        'site_youtube' => ['https://www.youtube.com/@taketwostudiobg', 'YouTube канал (пълен URL)'],
        // Public Maps URL of the Business Profile (cid = place id 0x75e57676e9371866).
        'site_google_maps' => ['https://www.google.com/maps?cid=8495326525195229286', 'Google Business Profile (Maps URL с cid)'],
    ];

    public function up(): void
    {
        foreach (self::VALUES as $key => [$value, $description]) {
            $current = DB::table('site_settings')->where('setting_key', $key)->first();

            if (! $current) {
                DB::table('site_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'description' => $description, 'created_at' => now(), 'updated_at' => now()]);
            } elseif (trim((string) $current->setting_value) === '') {
                DB::table('site_settings')->where('setting_key', $key)->update(['setting_value' => $value, 'description' => $description, 'updated_at' => now()]);
            }
        }

        \App\Support\Settings::forget();
    }

    public function down(): void
    {
        foreach (self::VALUES as $key => [$value]) {
            DB::table('site_settings')->where('setting_key', $key)->where('setting_value', $value)->update(['setting_value' => '']);
        }

        \App\Support\Settings::forget();
    }
};

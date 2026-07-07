<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['id' => 1, 'setting_key' => 'site_name',        'setting_value' => 'Take Two Studio 1603',           'description' => 'Name of the site'],
            ['id' => 2, 'setting_key' => 'site_tagline',     'setting_value' => 'Запечатваме вашите моменти завинаги', 'description' => 'Site tagline'],
            ['id' => 3, 'setting_key' => 'site_address',     'setting_value' => 'ж.к. Възраждане IV 1603, Варна',  'description' => 'Business address'],
            ['id' => 4, 'setting_key' => 'site_phone',       'setting_value' => '088 619 0124',                   'description' => 'Contact phone'],
            ['id' => 5, 'setting_key' => 'site_email',       'setting_value' => 'taketwostudio1603@gmail.com',    'description' => 'Contact email'],
            ['id' => 6, 'setting_key' => 'site_instagram',   'setting_value' => 'https://instagram.com/taketwostudio1603', 'description' => 'Instagram URL'],
            ['id' => 7, 'setting_key' => 'site_facebook',    'setting_value' => 'https://facebook.com/taketwostudio1603', 'description' => 'Facebook URL'],
            ['id' => 8, 'setting_key' => 'site_tiktok',      'setting_value' => 'https://tiktok.com/@taketwostudio1603', 'description' => 'TikTok URL'],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['id' => $setting['id']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

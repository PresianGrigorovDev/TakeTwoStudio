<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['setting_key' => 'site_name',                  'setting_value' => 'Take Two Studio 1603',                        'description' => 'Име на студиото'],
            ['setting_key' => 'site_tagline',               'setting_value' => 'Запечатваме вашите моменти завинаги',          'description' => 'Слоган'],
            ['setting_key' => 'site_address',               'setting_value' => Settings::DEFAULT_ADDRESS,                     'description' => 'Адрес (както е в Google Business Profile)'],
            ['setting_key' => 'site_phone',                 'setting_value' => Settings::DEFAULT_PHONE,                       'description' => 'Основен телефон, формат +359...'],
            ['setting_key' => 'site_phone_secondary',       'setting_value' => '+359894200634',                               'description' => 'Втори телефон (само за абитуриентски балове), формат +359...'],
            ['setting_key' => 'site_phone_secondary_label', 'setting_value' => 'Абитуриентски балове',                         'description' => 'Етикет на втория телефон'],
            ['setting_key' => 'site_email',                 'setting_value' => Settings::DEFAULT_EMAIL,                       'description' => 'Имейл за контакт (наблюдаван)'],
            ['setting_key' => 'site_instagram',             'setting_value' => 'https://www.instagram.com/taketwostudio1603', 'description' => 'Instagram профил'],
            ['setting_key' => 'site_facebook',              'setting_value' => 'https://www.facebook.com/taketwostudio1603',  'description' => 'Facebook страница'],
            ['setting_key' => 'site_tiktok',                'setting_value' => 'https://www.tiktok.com/@taketwostudio1603',   'description' => 'TikTok профил'],
            ['setting_key' => 'site_youtube',               'setting_value' => '',                                            'description' => 'YouTube канал (пълен URL) - попълни!'],
            ['setting_key' => 'site_google_maps',           'setting_value' => '',                                            'description' => 'Google Maps / Business Profile URL - попълни!'],
        ];
        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['setting_key' => $setting['setting_key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

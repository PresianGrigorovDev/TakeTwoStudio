<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One canonical set of NAP keys (site_*), phone numbers in E.164, and the
 * legacy duplicates (contact_*, social_*) removed so no view can pick the
 * wrong one again. Idempotent: safe to re-run.
 */
return new class extends Migration
{
    private const LEGACY_KEYS = ['contact_phone', 'contact_email', 'contact_address', 'social_facebook', 'social_instagram'];

    public function up(): void
    {
        $rows = DB::table('site_settings')->pluck('setting_value', 'setting_key');

        $value = fn (string ...$keys) => collect($keys)->map(fn ($k) => trim((string) ($rows[$k] ?? '')))->first(fn ($v) => $v !== '');

        $this->set('site_name', $value('site_name') ?? 'Take Two Studio 1603', 'Име на студиото');
        $this->set('site_tagline', $value('site_tagline') ?? 'Запечатваме вашите моменти завинаги', 'Слоган');
        $this->set('site_address', $value('site_address', 'contact_address') ?? Settings::DEFAULT_ADDRESS, 'Адрес (както е в Google Business Profile)');
        $this->set('site_phone', Settings::toE164($value('site_phone', 'contact_phone')) ?? Settings::DEFAULT_PHONE, 'Основен телефон, формат +359...');
        $this->set('site_phone_secondary', Settings::toE164($value('site_phone_secondary')) ?? '+359894200634', 'Втори телефон (само за абитуриентски балове), формат +359...');
        $this->set('site_phone_secondary_label', $value('site_phone_secondary_label') ?? 'Абитуриентски балове', 'Етикет на втория телефон');
        $this->set('site_email', $value('site_email') ?? Settings::DEFAULT_EMAIL, 'Имейл за контакт (наблюдаван)');
        $this->set('site_facebook', $value('site_facebook', 'social_facebook') ?? 'https://www.facebook.com/taketwostudio1603', 'Facebook страница');
        $this->set('site_instagram', $value('site_instagram', 'social_instagram') ?? 'https://www.instagram.com/taketwostudio1603', 'Instagram профил');
        $this->set('site_tiktok', $value('site_tiktok') ?? 'https://www.tiktok.com/@taketwostudio1603', 'TikTok профил');
        $this->set('site_youtube', $value('site_youtube') ?? '', 'YouTube канал (пълен URL) - попълни!', keepEmpty: true);
        $this->set('site_google_maps', $value('site_google_maps') ?? '', 'Google Maps / Business Profile URL - попълни!', keepEmpty: true);

        DB::table('site_settings')->whereIn('setting_key', self::LEGACY_KEYS)->delete();

        Settings::forget();
    }

    public function down(): void
    {
        $rows = DB::table('site_settings')->pluck('setting_value', 'setting_key');

        $this->set('contact_phone', $rows['site_phone'] ?? Settings::DEFAULT_PHONE, 'Primary contact phone');
        $this->set('contact_email', $rows['site_email'] ?? Settings::DEFAULT_EMAIL, 'Primary contact email');
        $this->set('contact_address', $rows['site_address'] ?? Settings::DEFAULT_ADDRESS, 'Primary contact address');
        $this->set('social_facebook', $rows['site_facebook'] ?? '', 'Facebook page URL');
        $this->set('social_instagram', $rows['site_instagram'] ?? '', 'Instagram profile URL');

        Settings::forget();
    }

    private function set(string $key, string $value, string $description, bool $keepEmpty = false): void
    {
        $exists = DB::table('site_settings')->where('setting_key', $key)->exists();

        if ($exists) {
            $update = ['description' => $description, 'updated_at' => now()];

            if ($value !== '' || ! $keepEmpty) {
                $update['setting_value'] = $value;
            }

            DB::table('site_settings')->where('setting_key', $key)->update($update);

            return;
        }

        DB::table('site_settings')->insert([
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};

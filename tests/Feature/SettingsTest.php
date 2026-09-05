<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_come_from_the_database_by_key_and_cache_is_invalidated_on_save(): void
    {
        $this->seed();

        $this->assertSame('+359886190124', Settings::phone());
        $this->assertSame('+359894200634', Settings::phoneSecondary());
        $this->assertSame('taketwostudio1603@gmail.com', Settings::email());
        $this->assertSame('ж.к. Възраждане IV 1603', Settings::streetAddress());
        $this->assertArrayHasKey('instagram', Settings::socialLinks());
        $this->assertArrayNotHasKey('youtube', Settings::socialLinks(), 'empty URLs must be skipped');

        SiteSetting::where('setting_key', 'site_phone')->first()->update(['setting_value' => '089 111 2233']);

        $this->assertSame('+359891112233', Settings::phone(), 'saving a setting must bust the cache and normalise the phone');
    }

    public function test_migration_removed_legacy_duplicate_keys(): void
    {
        $this->seed();

        $this->assertSame(0, SiteSetting::whereIn('setting_key', ['contact_phone', 'contact_email', 'contact_address', 'social_facebook', 'social_instagram'])->count());
        $this->assertSame(1, SiteSetting::where('setting_key', 'site_phone')->count());
    }
}

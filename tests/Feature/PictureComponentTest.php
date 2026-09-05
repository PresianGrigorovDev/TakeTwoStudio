<?php

namespace Tests\Feature;

use App\Support\Images;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PictureComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_webp_sibling_is_served_when_it_exists(): void
    {
        $this->assertFileExists(public_path('css/img/header.webp'));

        $html = Blade::render('<x-picture :src="asset(\'css/img/header.jpg\')" alt="Hero" class="w-100" />');

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('type="image/webp" srcset="'.asset('css/img/header.webp').'"', $html);
        $this->assertStringContainsString('src="'.asset('css/img/header.jpg').'"', $html);
        $this->assertDoesNotMatchRegularExpression('/\s(width|height)="/', $html, 'CSS owns the sizing: no fixed width/height attributes');
        $this->assertStringContainsString('loading="lazy" decoding="async"', $html);
        $this->assertStringContainsString('class="w-100"', $html);
    }

    public function test_plain_img_without_webp_and_eager_mode(): void
    {
        $html = Blade::render('<x-picture :src="asset(\'css/img/logo-tts-white.webp\')" alt="Logo" :eager="true" width="787" height="389" />');

        $this->assertStringNotContainsString('<picture>', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringNotContainsString('loading="lazy"', $html);
        $this->assertDoesNotMatchRegularExpression('/\s(width|height)="/', $html, 'explicitly passed width/height are dropped too');

        $this->assertNull(Images::webpUrl('https://images.pixieset.com/some/photo.jpg'), 'foreign URLs are never rewritten');
        $this->assertNull(Images::localPath('/../.env'));
    }

    public function test_service_pages_have_no_repeated_inline_gallery_styles(): void
    {
        $this->seed();

        foreach (['/weddings', '/baptism', '/family', '/automotive', '/architectural'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringNotContainsString('style="height: 70px; width: 100px;', $html, $path);
            $this->assertStringNotContainsString('style="height: 65vh;', $html, $path);
            $this->assertStringNotContainsString('style="cursor: pointer;"', $html, $path);
        }
    }
}

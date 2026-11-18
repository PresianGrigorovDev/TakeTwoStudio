<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PromPortfolioPhoto;
use App\Models\TeamMember;
use App\Models\WeddingGallery;
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

    public function test_public_pages_rebase_legacy_images_and_gallery_links_to_the_current_host(): void
    {
        $this->seed();
        $lara = 'https://lara.taketwostudio1603.com';
        $master = 'https://www.taketwostudio1603.com';
        $external = 'https://images.pixieset.com/example/photo.jpg';
        config(['app.url' => $lara]);

        Partner::query()->where('display_order', 1)->update([
            'logo_path' => $master.'/public/css/img/Logos/Ardes%20logo%20without%20slogan%20expanded@4x.png',
        ]);
        TeamMember::query()->where('display_order', 1)->update(['image_path' => $master.'/css/img/ST.png']);
        $gallery = WeddingGallery::create([
            'title' => 'Тестова сватба',
            'slug' => 'legacy-images',
            'is_active' => true,
            'cover_image' => $master.'/public/css/img/header.jpg',
        ]);
        $gallery->photos()->create(['image_path' => $external, 'sort_order' => 1]);
        PromPortfolioPhoto::create(['image_path' => 'prom_portfolio_photos/class.jpg', 'is_visible' => true]);

        $this->get($lara.'/')->assertOk()
            ->assertSee($lara.'/css/img/Logos/Ardes%20logo%20without%20slogan%20expanded@4x.png', false)
            ->assertSee('data-src="'.$lara.'/css/img/ST.webp"', false)
            ->assertDontSee($master, false);
        $this->get($lara.'/za-nas')->assertOk()
            ->assertSee('srcset="'.$lara.'/css/img/ST.webp"', false)
            ->assertSee('src="'.$lara.'/css/img/ST.png"', false);
        $this->get($lara.'/weddings')->assertOk()
            ->assertSee('src="'.$lara.'/css/img/header.jpg"', false)
            ->assertSee('src="'.$external.'"', false);
        $this->get($lara.'/svatbi/legacy-images')->assertOk()
            ->assertSee($lara.'/css/img/header.jpg', false)
            ->assertSee('href="'.$external.'"', false);
        $this->get($lara.'/proms')->assertOk()
            ->assertSee('href="'.$lara.'/storage/prom_portfolio_photos/class.jpg"', false);
        $this->get($lara.'/sitemap-images.xml')->assertOk()
            ->assertSee($lara.'/css/img/header.jpg', false)
            ->assertDontSee($master, false);
    }

    public function test_rebasing_inline_blog_images_preserves_query_values_and_external_links(): void
    {
        $source = 'https://taketwostudio1603.com/public/storage/blog/attachments/file%20name.jpg?v=1&amp;x=2#photo';
        $html = '<a href="'.$source.'"><img alt="Снимка" src="'.$source.'"></a>'
            .'<img src="https://cdn.example.com/photo.jpg">';

        $result = Images::rebaseHtmlImages($html);

        $this->assertStringContainsString('href="'.$source.'"', $result);
        $this->assertStringContainsString('src="'.asset('storage/blog/attachments/file%20name.jpg?v=1&x=2#photo').'"', html_entity_decode($result));
        $this->assertStringContainsString('src="https://cdn.example.com/photo.jpg"', $result);
    }
}

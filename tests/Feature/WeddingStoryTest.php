<?php

namespace Tests\Feature;

use App\Models\WeddingGallery;
use App\Models\WeddingGalleryPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingStoryTest extends TestCase
{
    use RefreshDatabase;

    private function gallery(array $overrides = []): WeddingGallery
    {
        $gallery = WeddingGallery::create(array_merge([
            'title' => 'Мария и Иван',
            'slug' => 'maria-i-ivan-evksinograd',
            'cover_image' => 'wedding_galleries/covers/cover.jpg',
            'event_date' => '2026-06-20',
            'venue' => 'Евксиноград',
            'location' => 'Варна',
            'description' => "Церемония в парка, снимки по алеите и парти до късно.\nДронът летя над морето при залез.",
            'couple_quote' => 'Кадрите са точно каквито си представяхме.',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_active' => true,
        ], $overrides));

        foreach ([1, 2, 3] as $i) {
            WeddingGalleryPhoto::create(['wedding_gallery_id' => $gallery->id, 'image_path' => "wedding_galleries/photos/{$i}.jpg", 'sort_order' => $i]);
        }

        return $gallery;
    }

    public function test_story_page_renders_with_venue_photos_film_and_schema(): void
    {
        $this->seed();
        $gallery = $this->gallery();

        $response = $this->get('/svatbi/'.$gallery->slug)->assertOk();
        $html = $response->getContent();

        $response->assertSee('Сватбата на Мария и Иван – Евксиноград, Варна');
        $response->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
        $this->assertSame(3, substr_count($html, 'class="glightbox story-photo"'));
        $this->assertLessThanOrEqual(60, mb_strlen(preg_match('#<title>(.*?)</title>#', $html, $t) ? $t[1] : ''));

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $graph = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR)['@graph'];
        $types = collect($graph)->flatMap(fn ($n) => (array) $n['@type']);

        $this->assertTrue($types->contains('ImageGallery'));
        $this->assertTrue($types->contains('Place'));
        $this->assertTrue($types->contains('VideoObject'));
        $this->assertSame(3 + 1, $types->filter(fn ($t) => $t === 'ImageObject')->count(), '3 photos + primary image');
        $crumbs = collect($graph)->first(fn ($n) => $n['@type'] === 'BreadcrumbList');
        $this->assertSame(['Начало', 'Сватби', 'Сватбата на Мария и Иван'], array_column($crumbs['itemListElement'], 'name'));
    }

    public function test_inactive_gallery_is_not_public_and_weddings_page_links_active_stories(): void
    {
        $this->seed();
        $this->gallery();
        $this->gallery(['title' => 'Скрита', 'slug' => 'skrita', 'is_active' => false]);

        $this->get('/svatbi/skrita')->assertNotFound();
        $this->get('/weddings')->assertOk()->assertSee('/svatbi/maria-i-ivan-evksinograd', false)->assertDontSee('/svatbi/skrita', false);

        $body = $this->get('/sitemap-pages.xml')->assertOk()->getContent();
        $this->assertStringContainsString(route('weddings.story', 'maria-i-ivan-evksinograd'), $body);
        $this->assertStringNotContainsString('skrita', $body);
        $this->assertStringContainsString('wedding_galleries/photos/1.jpg', $this->get('/sitemap-images.xml')->getContent());
    }
}

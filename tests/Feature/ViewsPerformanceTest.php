<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\TeamMember;
use App\Models\WeddingGallery;
use Database\Seeders\BlogCategorySeeder;
use Database\Seeders\BlogPostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PageSpeed (mobile) guarantees in the Blade views (docs/PAGESPEED-PLAN.md, phases 2-3, K1/K4/K5):
 * the LCP hero is a real high-priority <img>, nothing above the fold waits for AOS,
 * team modal photos are not requested before the modal opens, and every self-hosted
 * CSS/JS URL carries ?v= so the 1-year immutable cache never serves a stale file.
 */
class ViewsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function test_home_hero_is_a_high_priority_img_and_not_hidden_by_aos(): void
    {
        $this->seed();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<header class="hero">\s*<img [^>]*class="hero-bg-img"#', $html, 'the hero background <img> must be the first child of .hero');
        $this->assertMatchesRegularExpression('#<img(?=[^>]*class="hero-bg-img")(?=[^>]*fetchpriority="high")(?=[^>]*src="'.preg_quote(asset('css/img/header.webp'), '#').'")[^>]*>#', $html);
        $this->assertDoesNotMatchRegularExpression('#<img[^>]*class="hero-bg-img"[^>]*loading="lazy"#', $html);
        $this->assertMatchesRegularExpression('#<div class="hero-content">#', $html);
        $this->assertDoesNotMatchRegularExpression('#class="hero-content"[^>]*data-aos#', $html);

        // The preload (if kept) must point at the very same URL, otherwise the hero downloads twice.
        if (preg_match_all('#<link rel="preload"[^>]*as="image"[^>]*>#', $html, $preloads)) {
            foreach ($preloads[0] as $preload) {
                if (str_contains($preload, 'header.')) {
                    $this->assertStringContainsString('href="'.asset('css/img/header.webp').'"', $preload);
                }
            }
        }
    }

    public static function heroPages(): array
    {
        return [
            ['/weddings'], ['/proms'], ['/baptism'], ['/commercial'], ['/family'], ['/portrait'],
            ['/automotive'], ['/architectural'], ['/events'], ['/booking'], ['/blog'],
            ['/ceni'], ['/za-nas'], ['/kontakti'], ['/abiturientski-bal-varna'],
        ];
    }

    #[DataProvider('heroPages')]
    public function test_hero_titles_are_not_hidden_by_aos(string $path): void
    {
        $this->seed();

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertNoAosOnHero($html, $path);
    }

    public function test_blog_post_and_first_blog_card_are_not_hidden_by_aos(): void
    {
        $this->seed();
        $this->seed([BlogCategorySeeder::class, BlogPostSeeder::class]);
        $post = BlogPost::published()->latest('published_at')->firstOrFail();

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();
        $this->assertNoAosOnHero($html, 'blog post');

        $html = $this->get('/blog')->assertOk()->getContent();
        preg_match_all('#<article class="blog-card"[^>]*>#', $html, $cards);
        $this->assertGreaterThan(1, count($cards[0]));
        $this->assertStringNotContainsString('data-aos', $cards[0][0], 'the first blog card is above the fold');
        $this->assertStringContainsString('data-aos', $cards[0][1], 'the rest of the cards keep their animation');
        $this->assertMatchesRegularExpression('#<article class="blog-card">\s*<a [^>]*>\s*<img (?![^>]*loading="lazy")[^>]*class="blog-card__image"#', $html, 'the first card image is not lazy-loaded');
    }

    public function test_wedding_story_hero_is_not_hidden_by_aos(): void
    {
        $this->seed();
        $gallery = WeddingGallery::create([
            'title' => 'Мария и Иван', 'slug' => 'maria-i-ivan', 'cover_image' => 'wedding_galleries/covers/cover.jpg',
            'event_date' => '2026-06-20', 'venue' => 'Евксиноград', 'location' => 'Варна', 'is_active' => true,
        ]);

        $html = $this->get('/svatbi/'.$gallery->slug)->assertOk()->getContent();

        $this->assertNoAosOnHero($html, 'wedding story');
    }

    public function test_team_modal_photos_load_only_when_the_modal_opens(): void
    {
        $this->seed();
        // A local photo with a WebP sibling: the modal must reuse the WebP URL the grid already downloaded.
        TeamMember::create(['name' => 'Тест Екип', 'role_bg' => 'Фотограф', 'image_path' => asset('css/img/prom.jpg'), 'is_active' => true, 'display_order' => 99]);
        $members = TeamMember::where('is_active', true)->get();

        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('#<div class="modal-body text-center">\s*(.*?)<p class="role-text#s', $html, $bodies);
        $this->assertCount($members->count(), $bodies[1]);

        foreach ($bodies[1] as $body) {
            $this->assertStringNotContainsString('<picture', $body);
            $this->assertStringNotContainsString('srcset', $body);
            $this->assertMatchesRegularExpression('#<img class="team-img team-modal-img mb-3 lazy-modal-img" src="'.preg_quote(self::PIXEL, '#').'"\s+data-src="[^"]+"#', $body);
            $this->assertDoesNotMatchRegularExpression('#\s(style|width|height)=#', $body, 'the modal photo is sized by .team-modal-img in style.css');
        }

        $this->assertStringContainsString('data-src="'.asset('css/img/prom.webp').'"', $html);
        $this->assertStringContainsString("addEventListener('show.bs.modal'", $html);
    }

    public static function versionedPages(): array
    {
        return [
            ['/'], ['/weddings'], ['/proms'], ['/baptism'], ['/commercial'], ['/family'], ['/portrait'],
            ['/automotive'], ['/architectural'], ['/events'], ['/booking'], ['/blog'],
            ['/ceni'], ['/za-nas'], ['/kontakti'], ['/abiturientski-bal-varna'], ['/privacy'],
        ];
    }

    /** Page-specific css/js pushed by the views (@push('styles') / @push('scripts')). */
    public function test_css_and_js_pushed_by_the_views_are_versioned(): void
    {
        $this->seed();
        $this->seed([BlogCategorySeeder::class, BlogPostSeeder::class]);
        $layoutOwned = '#/(vendor/(bootstrap|fontawesome|aos)|fonts)/|/css/(style|components)\.css#';
        $seen = [];

        foreach (['/weddings', '/proms', '/baptism', '/commercial', '/family', '/portrait', '/automotive', '/architectural', '/events', '/booking', '/blog', '/blog/'.BlogPost::published()->value('slug')] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            foreach ($this->localAssetUrls($html) as $url) {
                if (preg_match($layoutOwned, $url)) {
                    continue;
                }
                $seen[] = $url;
                $this->assertMatchesRegularExpression('#\?v=\d+$#', $url, "$path: $url is cached for a year without ?v= cache busting");
            }
        }

        $files = array_map(fn ($url) => strtok(substr($url, strlen(url('/')) + 1), '?'), $seen);
        foreach (['css/weddings.css', 'css/proms.css', 'css/baptism.css', 'css/commercial.css', 'css/booking.css', 'css/blog.css',
            'vendor/glightbox/glightbox.min.css', 'vendor/glightbox/glightbox.min.js', 'js/calculators/wedding.js', 'js/calculators/prom.js',
            'js/calculators/baptism.js', 'js/calculators/generic.js', 'js/commercial.js', 'js/booking-calendar.js'] as $file) {
            $this->assertContains($file, $files, "$file is no longer pushed by any view (update this test)");
        }
    }

    /** Whole page, layout included (K1: a 1-year immutable cache needs ?v= on every self-hosted css/js). */
    #[DataProvider('versionedPages')]
    public function test_every_self_hosted_css_and_js_url_is_versioned(string $path): void
    {
        $this->seed();

        $html = $this->get($path)->assertOk()->getContent();

        $urls = $this->localAssetUrls($html);
        $this->assertNotEmpty($urls, "$path: no self-hosted css/js found (regex out of date?)");

        foreach ($urls as $url) {
            $this->assertMatchesRegularExpression('#\?v=\d+$#', $url, "$path: $url is cached for a year without ?v= cache busting");
        }
    }

    /** No data-aos on any hero element (title, subtitle, content wrapper, post excerpt/body). */
    private function assertNoAosOnHero(string $html, string $where): void
    {
        foreach (['hero-title', 'hero-content', 'page-hero', 'blog-hero__title', 'blog-hero__subtitle', 'blog-post__title', 'blog-post__excerpt', 'blog-post__body', 'booking-calendar-wrapper'] as $class) {
            $this->assertDoesNotMatchRegularExpression('#<[a-z0-9]+ [^>]*class="[^"]*\b'.$class.'\b[^"]*"[^>]*data-aos#', $html, "$where: .$class waits for AOS");
        }

        // Every <h1> is above the fold: neither it nor its opening wrapper may carry data-aos.
        $this->assertDoesNotMatchRegularExpression('#<h1[^>]*data-aos#', $html, "$where: <h1> waits for AOS");
    }

    /** @return list<string> href/src of every stylesheet and script served from this app's /css, /js, /vendor or /fonts. */
    private function localAssetUrls(string $html): array
    {
        $base = preg_quote(rtrim(url('/'), '/'), '#');
        preg_match_all('#<(?:link|script)\b[^>]*\b(?:href|src)="('.$base.'/(?:css|js|vendor|fonts)/[^"]+?\.(?:css|js)(?:\?[^"]*)?)"#', $html, $m);

        return array_values(array_unique($m[1]));
    }
}

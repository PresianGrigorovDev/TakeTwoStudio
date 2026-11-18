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

    public function test_blog_post_and_first_blog_cards_are_not_hidden_by_aos(): void
    {
        $this->seed();
        $this->seed([BlogCategorySeeder::class, BlogPostSeeder::class]);
        $post = BlogPost::published()->latest('published_at')->firstOrFail();

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();
        $this->assertNoAosOnHero($html, 'blog post');

        $html = $this->get('/blog')->assertOk()->getContent();
        preg_match_all('#<article class="blog-card"[^>]*>#', $html, $cards);
        $this->assertGreaterThan(3, count($cards[0]));
        foreach ([0, 1, 2] as $i) {
            // cards 1-3 share the first screen at 1350px: with AOS they render, vanish, then fade in again
            $this->assertStringNotContainsString('data-aos', $cards[0][$i], 'blog card '.($i + 1).' is in the first desktop screen');
        }
        $this->assertStringContainsString('data-aos', $cards[0][3], 'the cards further down keep their animation');

        preg_match_all('#<img [^>]*class="blog-card__image"[^>]*>#', $html, $images);
        $this->assertStringNotContainsString('loading="lazy"', $images[0][0], 'the first card image (mobile LCP) is not lazy-loaded');
        $this->assertStringContainsString('fetchpriority="high"', $images[0][0]);
        $this->assertStringContainsString('loading="lazy"', $images[0][1]);
    }

    /** axe "heading-order": a heading may only go one level deeper than the previous visible heading. */
    public static function headingPages(): array
    {
        return [
            ['/'], ['/weddings'], ['/proms'], ['/baptism'], ['/commercial'], ['/family'], ['/portrait'],
            ['/automotive'], ['/architectural'], ['/events'], ['/booking'], ['/blog'], ['/blog/category/wedding-tips'],
            ['/ceni'], ['/za-nas'], ['/kontakti'], ['/abiturientski-bal-varna'], ['/privacy'],
        ];
    }

    #[DataProvider('headingPages')]
    public function test_heading_levels_never_skip(string $path): void
    {
        $this->seed();
        $this->seed([BlogCategorySeeder::class, BlogPostSeeder::class]);

        $html = $this->get($path)->assertOk()->getContent();

        // Modals are hidden (axe skips them); drop them before reading the outline.
        $html = preg_replace('#<div class="modal fade".*?<div class="modal-body#s', '', $html);
        preg_match_all('#<h([1-6])\b[^>]*>(.*?)</h\1>#s', $html, $m, PREG_SET_ORDER);
        $previous = 0;
        foreach ($m as [$tag, $level, $text]) {
            if (str_contains($tag, 'modal-title')) {
                continue;
            }
            $this->assertLessThanOrEqual($previous + 1, (int) $level, "$path: <h$level> «".trim(strip_tags($text))."» skips a level after <h$previous>");
            $previous = (int) $level;
        }
        $this->assertGreaterThan(0, $previous);
    }

    public function test_footer_headings_are_h2(): void
    {
        $this->seed();

        $html = $this->get('/kontakti')->assertOk()->getContent();
        preg_match('#<footer.*</footer>#s', $html, $footer);

        $this->assertSame(3, substr_count($footer[0], '<h2>'));
        $this->assertStringNotContainsString('<h3', $footer[0]);
        $this->assertStringContainsString("footer h2,\nfooter h3 {", file_get_contents(public_path('css/style.css')), 'footer h2 must keep the old footer h3 look');
    }

    public function test_service_dropdown_renders_no_empty_icon(): void
    {
        $this->seed();

        $this->assertStringNotContainsString('class="fas fa-"', $this->get('/')->assertOk()->getContent());
    }

    public function test_home_has_no_dead_lightbox_markup(): void
    {
        $this->seed();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="lightbox"', $html);
        $this->assertStringNotContainsString('id="lightbox-img"', $html);
    }

    public static function galleryPages(): array
    {
        return [['/weddings'], ['/proms'], ['/baptism'], ['/commercial'], ['/family'], ['/portrait'], ['/automotive'], ['/architectural'], ['/events']];
    }

    #[DataProvider('galleryPages')]
    public function test_glightbox_css_does_not_block_rendering(string $path): void
    {
        $this->seed();

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<link rel="stylesheet" href="[^"]*/vendor/glightbox/glightbox\.min\.css\?v=\d+" media="print" onload="this\.media=\'all\'">#', $html);
        $this->assertDoesNotMatchRegularExpression('#<link rel="stylesheet" href="[^"]*glightbox\.min\.css[^"]*">\s*(?!</noscript>)#', $html, "$path: glightbox.min.css must not be render-blocking");
    }

    /** When no Filament hero image is set, the CSS fallback hero is preloaded with the very URL the CSS image-set picks. */
    public static function fallbackHeroes(): array
    {
        return [
            ['/weddings', 'css/img/Сватба.webp', 'weddings.css'],
            ['/proms', 'css/img/proms-hero.webp', 'proms.css'],
            ['/commercial', 'css/img/ads/moonlight.webp', 'commercial.css'],
            ['/baptism', 'css/img/krustene.webp', 'baptism.css'],
            ['/family', 'css/img/krustene.webp', 'baptism.css'],
            ['/portrait', 'css/img/krustene.webp', 'baptism.css'],
            ['/automotive', 'css/img/krustene.webp', 'baptism.css'],
            ['/architectural', 'css/img/krustene.webp', 'baptism.css'],
            ['/events', 'css/img/krustene.webp', 'baptism.css'],
        ];
    }

    #[DataProvider('fallbackHeroes')]
    public function test_fallback_hero_is_preloaded_with_the_css_url(string $path, string $image, string $css): void
    {
        $this->seed();
        \App\Models\Service::query()->update(['hero_image' => null]);

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertFileExists(public_path($image));
        $this->assertStringContainsString('<link rel="preload" href="'.asset($image).'" as="image" type="image/webp" fetchpriority="high">', $html);
        $this->assertStringContainsString("url('../".$image."') type('image/webp')", file_get_contents(public_path('css/'.$css)), "$css no longer uses $image: update the preload in the view");
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

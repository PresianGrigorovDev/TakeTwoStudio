<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\FamilyPackage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Facts that AI assistants read must be consistent everywhere (SEO/GEO round 2, 2026-11-02):
 * one phone, delivery times and opening hours from the database, plain-text llms files.
 */
class AiVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const SECONDARY_PHONES = ['089 420 0634', '0894200634', '+359894200634', '089 437 3618', '0894373618'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_llms_files_are_plain_text_with_one_phone_and_database_delivery_days(): void
    {
        Service::where('slug', 'proms')->update(['delivery_days' => 70]);
        Service::where('slug', 'weddings')->update(['delivery_days' => 100]);
        TeamMember::query()->update(['phone' => '0894373618']);

        foreach (['/llms.txt', '/llms-full.txt'] as $path) {
            $text = $this->get($path)->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();

            $this->assertStringContainsString(Settings::phoneDisplay(Settings::phone()), $text, $path);
            foreach (self::SECONDARY_PHONES as $phone) {
                $this->assertStringNotContainsString($phone, $text, "$path must publish only the studio number");
            }
            $this->assertMatchesRegularExpression('/: до 70\b/u', $text, "$path delivery days come from services.delivery_days");
            $this->assertMatchesRegularExpression('/: до 100\b/u', $text, $path);
            $this->assertDoesNotMatchRegularExpression('/&(quot|amp|#039|lt|gt);/', $text, "$path must not contain HTML entities");
            $this->assertStringContainsString(url('/ceni'), $text, $path);
        }

        $full = $this->get('/llms-full.txt')->getContent();
        $this->assertStringNotContainsString('3–4 седмици', $full, 'the old hard-coded graduation FAQ block is gone');
    }

    public function test_opening_hours_come_from_site_settings(): void
    {
        SiteSetting::where('setting_key', 'site_hours_opens')->first()->update(['setting_value' => '10:00']);

        $this->assertSame('10:00', Settings::opensAt());
        $this->get('/kontakti')->assertOk()->assertSee('10:00')->assertDontSee('09:00');
        $this->get('/')->assertOk()->assertSee('"opens":"10:00"', false);
        $this->get('/llms.txt')->assertOk()->assertSee('всеки ден, 10:00–18:00');
    }

    public function test_secondary_and_personal_phones_are_not_published_on_pages(): void
    {
        TeamMember::query()->update(['phone' => '0894373618']);

        foreach (['/', '/proms', '/kontakti', '/weddings'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            foreach (self::SECONDARY_PHONES as $phone) {
                $this->assertStringNotContainsString($phone, $html, "$path still shows $phone");
            }
        }
    }

    public function test_viewing_a_post_does_not_change_its_modified_date(): void
    {
        $post = $this->makePost('obrabotka-snimki');
        $before = $post->updated_at->toDateTimeString();

        Carbon::setTestNow(now()->addDay());
        $this->get('/blog/obrabotka-snimki')->assertOk();
        Carbon::setTestNow();

        $post->refresh();
        $this->assertSame(1, (int) $post->views_count);
        $this->assertSame($before, $post->updated_at->toDateTimeString(), 'a page view must not bump dateModified / sitemap lastmod');
    }

    public function test_empty_blog_pages_are_404(): void
    {
        // (The /blog/SLUG -> /blog/slug 301 only matters on MySQL's case-insensitive
        // collation; sqlite compares case-sensitively, so it is not reproducible here.)
        $this->makePost('obrabotka-snimki');

        $this->get('/blog?page=99')->assertNotFound();
        $this->get('/blog')->assertOk();
    }

    public function test_price_table_keeps_leading_digits_of_features(): void
    {
        // Keys 1 and 2 used to become trim() masks and strip "1" / "2" from the text.
        FamilyPackage::create(['name' => 'Семейна', 'price_eur' => 150, 'features' => [1 => '1 локация', 2 => '25 обработени снимки'], 'is_visible' => true]);

        $this->get('/ceni')->assertOk()->assertSee('<li>1 локация</li>', false)->assertSee('<li>25 обработени снимки</li>', false);
    }

    public function test_robots_txt_opts_out_of_training_but_not_ai_search(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertMatchesRegularExpression('/User-agent: GPTBot\s+Disallow: \//', $robots);
        $this->assertStringNotContainsString('OAI-SearchBot\nDisallow', $robots);
        $this->assertMatchesRegularExpression('/User-agent: \*\s+Disallow: \/admin/', $robots);
    }

    private function makePost(string $slug): BlogPost
    {
        $category = BlogCategory::firstOrCreate(['slug' => 'saveti'], ['name' => 'Съвети', 'is_visible' => true]);

        return BlogPost::create([
            'title' => 'Обработка на снимки',
            'slug' => $slug,
            'excerpt' => 'Как обработваме снимките.',
            'body' => '<p>Текст.</p>',
            'cover_image' => 'css/img/social-share-cover.jpg',
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }
}

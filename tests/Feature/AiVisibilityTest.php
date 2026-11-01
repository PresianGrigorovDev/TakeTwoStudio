<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Faq;
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

    public function test_faq_migration_aligns_the_live_answers_with_the_owner_facts(): void
    {
        Faq::query()->delete();
        $live = [
            ['proms', 'Кога трябва да запазим дата за бала?', 'Препоръчваме да запазите дата минимум 7–8 месеца предварително.'],
            ['proms', 'Кога получаваме готовите снимки и видео?', 'Обработените материали се предават до 50 работни дни след събитието.'],
            ['proms', 'Кога трябва да резервирам?', 'Препоръчваме резервация поне 3–4 седмици преди бала.'],
            ['proms', 'Колко снимки ще получим и кога?', 'Получавате достъп до онлайн галерия в рамките на 5 работни дни след сесията.'],
            ['baptism', 'Кога получаваме снимките и видеото?', 'Стандартният срок е до 30 работни дни.'],
            ['baptism', 'Кога получаваме готовите снимки и видео?', 'Стандартният срок е до 20 работни дни.'],
            ['baptism', 'Колко време трае самото кръщене?', 'Дубликат.'],
            ['commercial', 'Кога трябва да резервирам?', 'Не е въпрос за бал и не се пипа.'],
        ];
        foreach ($live as $i => [$slug, $question, $answer]) {
            Faq::create(['page_slug' => $slug, 'question' => $question, 'answer' => $answer, 'sort_order' => $i]);
        }

        $migration = require database_path('migrations/2026_11_02_130000_align_faq_facts_with_owner_answers.php');
        $migration->up();

        $proms = Faq::forPageVisible('proms')->pluck('answer')->implode(' ');
        $this->assertStringContainsString('Няма краен срок', $proms);
        $this->assertStringContainsString('до 70 работни дни', $proms);
        $this->assertStringNotContainsString('3–4 седмици', $proms, 'pre-prom answers left /proms');
        $this->assertStringNotContainsString('50 работни дни', $proms);

        $this->assertStringContainsString('до 20 работни дни', Faq::forPageVisible('graduation')->pluck('answer')->implode(' '));
        $this->assertSame(['Кога получаваме снимките и видеото?'], Faq::forPageVisible('baptism')->pluck('question')->all());
        $this->assertSame('Не е въпрос за бал и не се пипа.', Faq::where('page_slug', 'commercial')->value('answer'));

        $migration->down();
        $this->assertSame(3, Faq::forPageVisible('baptism')->count(), 'down() restores the hidden rows');
        $this->assertSame(0, Faq::forPageVisible('graduation')->count());
    }

    public function test_team_experience_years_come_from_the_database(): void
    {
        $member = TeamMember::where('is_active', true)->orderBy('display_order')->first();
        $member->update(['career_start_year' => 2013]);

        $this->get('/za-nas')->assertOk()->assertSee('В професията от 2013 г.');
        $this->get('/llms-full.txt')->assertOk()->assertSee('Екипът снима професионално от 2013 г.')->assertSee('в професията от 2013 г.');
        $this->get('/')->assertOk()->assertDontSee('twitter:site', false);
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

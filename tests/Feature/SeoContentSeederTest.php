<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\Seo\PromSeason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_articles_are_created_as_drafts_only_once(): void
    {
        BlogCategory::create(['name' => 'Съвети за бала', 'slug' => 'prom-tips', 'is_visible' => true]);
        BlogCategory::create(['name' => 'Сватбени съвети', 'slug' => 'wedding-tips', 'is_visible' => true]);

        $this->seed(\Database\Seeders\SeoContentSeeder::class);
        $this->seed(\Database\Seeders\SeoContentSeeder::class);

        $season = PromSeason::year();
        $drafts = BlogPost::whereIn('slug', ["cena-fotograf-abiturientski-bal-varna-{$season}", "svatben-fotograf-varna-ceni-{$season}"])->get();

        $this->assertCount(2, $drafts);
        $this->assertTrue($drafts->every(fn ($p) => $p->is_published === false));
        $this->assertStringContainsString('на ученик', $drafts->firstWhere('slug', "cena-fotograf-abiturientski-bal-varna-{$season}")->body);

        $this->seed();
        $this->get("/blog/cena-fotograf-abiturientski-bal-varna-{$season}")->assertNotFound();
        $this->assertStringNotContainsString('cena-fotograf-abiturientski-bal-varna', $this->get('/sitemap-blog.xml')->getContent());
    }

    public function test_bgn_price_post_is_rewritten_to_eur_by_the_migration(): void
    {
        $category = BlogCategory::create(['name' => 'Съвети за бала', 'slug' => 'prom-tips', 'is_visible' => true]);
        BlogPost::create([
            'title' => 'Спестете бюджет: Защо 195 лв. на ученик за пълно фото и видео е най-добрата оферта във Варна',
            'slug' => 'speistete-budjet-balno-zasnemane-varna',
            'meta_title' => 'Цена за бално заснемане: 195 лв./ученик',
            'excerpt' => 'Анализ на цената от 195 лв. на ученик.',
            'body' => '<p>Пакетът струва 195 лв. на ученик.</p>',
            'cover_image' => 'css/img/social-share-cover.jpg',
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        (require database_path('migrations/2026_09_05_170000_fix_prom_price_blog_post_currency.php'))->up();

        $post = BlogPost::where('slug', 'speistete-budjet-balno-zasnemane-varna')->first();
        $this->assertStringNotContainsString('195 лв', $post->title.$post->meta_title.$post->excerpt.$post->body);
        $this->assertStringContainsString('100 €', $post->body);
    }
}

<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function xml(string $path): \SimpleXMLElement
    {
        $response = $this->get($path)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control'));

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, "$path is not well-formed XML");

        return $xml;
    }

    public function test_sitemap_index_lists_the_three_child_sitemaps(): void
    {
        $xml = $this->xml('/sitemap.xml');

        $locs = array_map('strval', iterator_to_array($xml->sitemap, false) ? array_map(fn ($s) => $s->loc, iterator_to_array($xml->sitemap, false)) : []);

        $this->assertSame([url('/sitemap-pages.xml'), url('/sitemap-blog.xml'), url('/sitemap-images.xml')], $locs);
    }

    public function test_pages_sitemap_has_real_lastmod_and_no_ignored_hints(): void
    {
        $response = $this->get('/sitemap-pages.xml')->assertOk();
        $xml = simplexml_load_string($response->getContent());

        $locs = array_map(fn ($u) => (string) $u->loc, iterator_to_array($xml->url, false));

        foreach (['/', '/proms', '/weddings', '/commercial', '/events', '/booking', '/blog', '/privacy'] as $path) {
            $this->assertContains($path === '/' ? rtrim(url('/'), '/').'/' : url($path), $locs);
        }

        $this->assertStringNotContainsString('<changefreq>', $response->getContent());
        $this->assertStringNotContainsString('<priority>', $response->getContent());
        $this->assertStringNotContainsString('/public/', $response->getContent());

        $proms = collect(iterator_to_array($xml->url, false))->first(fn ($u) => (string) $u->loc === url('/proms'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $proms->lastmod, 'lastmod must come from real data, formatted as a date');
    }

    public function test_blog_sitemap_lists_published_posts_and_categories(): void
    {
        $category = BlogCategory::create(['name' => 'Съвети', 'slug' => 'saveti', 'is_visible' => true]);
        BlogPost::create(['title' => 'Публикувана', 'slug' => 'publikuvana', 'excerpt' => 'x', 'body' => 'x', 'cover_image' => 'css/img/social-share-cover.jpg', 'category_id' => $category->id, 'is_published' => true, 'published_at' => now()->subDay()]);
        BlogPost::create(['title' => 'Чернова', 'slug' => 'chernova', 'excerpt' => 'x', 'body' => 'x', 'cover_image' => 'css/img/social-share-cover.jpg', 'category_id' => $category->id, 'is_published' => false]);

        $body = $this->get('/sitemap-blog.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('blog.show', 'publikuvana'), $body);
        $this->assertStringContainsString(route('blog.category', 'saveti'), $body);
        $this->assertStringNotContainsString('chernova', $body);
    }

    public function test_images_sitemap_is_well_formed_with_the_image_namespace(): void
    {
        $body = $this->get('/sitemap-images.xml')->assertOk()->getContent();

        $this->assertNotFalse(simplexml_load_string($body));
        $this->assertStringContainsString('xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', $body);
        $this->assertStringNotContainsString('/public/', $body);
    }
}

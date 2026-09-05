<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\Seo\IndexNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_without_a_key_or_https_url(): void
    {
        config(['services.indexnow.key' => null, 'app.url' => 'https://taketwostudio1603.com']);
        $this->assertFalse(IndexNow::enabled());

        config(['services.indexnow.key' => 'abc', 'app.url' => 'http://localhost']);
        $this->assertFalse(IndexNow::enabled());
    }

    public function test_submits_only_own_https_urls_with_key_location(): void
    {
        config(['services.indexnow.key' => 'k3y', 'app.url' => 'https://taketwostudio1603.com']);
        Http::fake([IndexNow::ENDPOINT => Http::response('', 200)]);

        $ok = IndexNow::submit(['https://taketwostudio1603.com/proms', 'https://evil.example/x', 'https://taketwostudio1603.com/proms']);

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            return $request->url() === IndexNow::ENDPOINT
                && $request['host'] === 'taketwostudio1603.com'
                && $request['key'] === 'k3y'
                && $request['keyLocation'] === 'https://taketwostudio1603.com/k3y.txt'
                && $request['urlList'] === ['https://taketwostudio1603.com/proms'];
        });
    }

    public function test_publishing_a_post_pings_indexnow_after_the_response(): void
    {
        config(['services.indexnow.key' => 'k3y', 'app.url' => 'https://taketwostudio1603.com']);
        URL::forceRootUrl('https://taketwostudio1603.com'); // what AppServiceProvider does in production
        URL::forceScheme('https');
        Http::fake([IndexNow::ENDPOINT => Http::response('', 202)]);

        $category = BlogCategory::create(['name' => 'Съвети', 'slug' => 'saveti', 'is_visible' => true]);
        BlogPost::create(['title' => 'Нов пост', 'slug' => 'nov-post', 'excerpt' => 'x', 'body' => 'x', 'cover_image' => 'css/img/social-share-cover.jpg', 'category_id' => $category->id, 'is_published' => true, 'published_at' => now()->subMinute()]);

        // afterResponse() jobs run on app termination; trigger it explicitly in the test.
        $this->app->terminate();

        Http::assertSent(fn ($request) => in_array('https://taketwostudio1603.com/blog/nov-post', $request['urlList'], true));
    }

    public function test_key_file_is_served_when_configured(): void
    {
        config(['services.indexnow.key' => 'k3y']);

        // Routes are registered at boot; re-register with the key in place.
        $this->refreshApplication();
        config(['services.indexnow.key' => 'k3y']);
        $this->app['router']->get('/k3y.txt', fn () => response('k3y', 200, ['Content-Type' => 'text/plain']));

        $this->get('/k3y.txt')->assertOk()->assertSee('k3y');
    }
}

<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 2026-09-05: 11 seeded posts pointed at css/img/events-cover.jpg, a file that had
 * never existed, so their cards, hero and og:image were broken. The file now ships
 * with the repo and a missing cover falls back to a placeholder instead of a 404.
 */
class BlogCoverImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_cover_files_exist(): void
    {
        $this->assertFileExists(public_path('css/img/events-cover.jpg'));
        $this->assertFileExists(public_path('css/img/events-cover.webp'));
        $this->assertFileExists(public_path(BlogPost::FALLBACK_COVER));
    }

    public function test_static_cover_resolves_or_falls_back(): void
    {
        $post = new BlogPost(['cover_image' => 'css/img/events-cover.jpg']);
        $this->assertSame(asset('css/img/events-cover.jpg'), $post->cover_image_url);

        $post = new BlogPost(['cover_image' => 'css/img/does-not-exist.jpg']);
        $this->assertSame(asset(BlogPost::FALLBACK_COVER), $post->cover_image_url);
        $this->assertSame(asset(BlogPost::FALLBACK_COVER), $post->og_image_url);
    }

    public function test_uploaded_cover_resolves_or_falls_back(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/real.jpg', 'x');

        $this->assertSame(asset('storage/blog/real.jpg'), (new BlogPost(['cover_image' => 'blog/real.jpg']))->cover_image_url);
        $this->assertSame(asset(BlogPost::FALLBACK_COVER), (new BlogPost(['cover_image' => 'blog/gone.jpg']))->cover_image_url);
        $this->assertNull((new BlogPost(['cover_image' => null]))->cover_image_url);
        $this->assertSame('https://cdn.example/x.jpg', (new BlogPost(['cover_image' => 'https://cdn.example/x.jpg']))->cover_image_url);
    }

    public function test_every_seeded_post_cover_is_a_real_file(): void
    {
        $this->seed(\Database\Seeders\BlogPostSeeder::class);

        BlogPost::query()->whereNotNull('cover_image')->each(function (BlogPost $post) {
            $this->assertFileExists(public_path($post->cover_image), $post->slug);
        });
    }
}

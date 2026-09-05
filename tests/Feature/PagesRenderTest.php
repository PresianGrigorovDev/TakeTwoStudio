<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Smoke test: every public page renders with seeded data and never leaks a
 * "/public/" URL, the classic symptom of the shared-hosting docroot problem.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            ['/'], ['/weddings'], ['/proms'], ['/baptism'], ['/commercial'], ['/family'],
            ['/portrait'], ['/automotive'], ['/architectural'], ['/events'],
            ['/blog'], ['/booking'], ['/privacy'], ['/terms'], ['/cookies'],
            ['/sitemap.xml'], ['/llms.txt'], ['/llms-full.txt'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_without_public_prefix(string $path): void
    {
        $this->seed();

        $response = $this->get($path);

        $response->assertOk();
        $this->assertStringNotContainsString('/public/', $response->getContent(), "$path leaks a /public/ URL");
    }

    public function test_graduation_redirects_permanently_to_proms(): void
    {
        $this->get('/graduation')->assertRedirect('/proms')->assertStatus(301);
    }

    public function test_removed_debug_routes_are_gone(): void
    {
        $this->seed();

        foreach (['/seed-all', '/test-email-send', '/clear-cache'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}

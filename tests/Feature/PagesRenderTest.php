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
            ['/ceni'], ['/za-nas'], ['/kontakti'],
            ['/sitemap.xml'], ['/llms.txt'], ['/llms-full.txt'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_without_public_prefix(string $path): void
    {
        $this->seed();

        $response = $this->get($path);

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('/public/', $html, "$path leaks a /public/ URL");

        if (str_starts_with($response->headers->get('Content-Type', ''), 'text/html')) {
            $this->assertStringNotContainsString('<style', $html, "$path has inline <style> (styles belong in /css)");
            $this->assertStringNotContainsString('name="csrf-token"', $html, "$path exposes the CSRF meta tag");
            $this->assertStringNotContainsString('onmouseover=', $html, "$path has inline event handlers");
        }
    }

    public function test_analytics_loads_only_after_cookie_consent(): void
    {
        $this->seed();
        config(['services.google_analytics.measurement_id' => 'G-TEST12345']);

        $html = $this->get('/')->assertOk()->getContent();

        // No tag in <head>: nothing is sent to Google before the visitor accepts.
        $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com', $html);
        // The consent banner carries the loader that injects gtag.js after "Приемам всички".
        $this->assertStringContainsString("'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId)", $html);
        $this->assertStringContainsString('"G-TEST12345"', $html);
    }

    public function test_promo_code_request_uses_the_form_token_not_a_meta_tag(): void
    {
        $this->seed();

        $html = $this->get('/weddings')->assertOk()->getContent();

        $this->assertStringContainsString('input[name="_token"]', $html);
        $this->assertStringContainsString('name="_token"', $html);
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

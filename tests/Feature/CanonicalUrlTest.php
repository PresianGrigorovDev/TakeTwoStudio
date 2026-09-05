<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tests\TestCase;

/**
 * Application-level canonical URL fallback (App\Http\Middleware\NormalizeCanonicalUrl).
 * The primary enforcement is the project-root .htaccess; this middleware must
 * behave identically so the site stays canonical even if the .htaccess is lost.
 */
class CanonicalUrlTest extends TestCase
{
    private const ROOT = 'https://taketwostudio1603.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        config([
            'app.url' => self::ROOT,
            'app.force_canonical' => true,
        ]);
    }

    /**
     * Simulate a request reaching public/index.php on a host whose docroot is the
     * project root. Built by hand (not $this->call()) because Laravel's test helper
     * trims trailing slashes, which is one of the variants under test.
     */
    private function request(string $method, string $url, array $server = [])
    {
        $server = array_merge([
            'SCRIPT_FILENAME' => '/home/acct/public_html/public/index.php',
            'SCRIPT_NAME' => '/index.php',
        ], $server);

        $request = Request::createFromBase(SymfonyRequest::create($url, $method, [], [], [], $server));

        $response = $this->app->make(Kernel::class)->handle($request);

        return $this->createTestResponse($response, $request);
    }

    public function test_clean_canonical_request_passes_through(): void
    {
        $this->request('GET', self::ROOT.'/up')->assertOk();
    }

    public function test_http_is_redirected_to_https(): void
    {
        $this->request('GET', 'http://taketwostudio1603.com/up')
            ->assertRedirect(self::ROOT.'/up')
            ->assertStatus(301);
    }

    public function test_www_is_redirected_to_bare_host(): void
    {
        $this->request('GET', 'https://www.taketwostudio1603.com/up')
            ->assertRedirect(self::ROOT.'/up')
            ->assertStatus(301);
    }

    public function test_public_base_path_is_stripped(): void
    {
        $this->request('GET', self::ROOT.'/public/up', ['SCRIPT_NAME' => '/public/index.php'])
            ->assertRedirect(self::ROOT.'/up')
            ->assertStatus(301);
    }

    public function test_public_root_redirects_to_home(): void
    {
        $this->request('GET', self::ROOT.'/public/', ['SCRIPT_NAME' => '/public/index.php'])
            ->assertRedirect(self::ROOT.'/')
            ->assertStatus(301);
    }

    public function test_index_php_base_path_is_stripped(): void
    {
        $this->request('GET', self::ROOT.'/index.php/up')
            ->assertRedirect(self::ROOT.'/up')
            ->assertStatus(301);
    }

    public function test_trailing_slash_is_removed(): void
    {
        $this->request('GET', self::ROOT.'/up/')
            ->assertRedirect(self::ROOT.'/up')
            ->assertStatus(301);
    }

    public function test_query_string_is_preserved_and_everything_collapses_into_one_hop(): void
    {
        $this->request('GET', 'http://www.taketwostudio1603.com/public/up/?utm_source=x&page=2', ['SCRIPT_NAME' => '/public/index.php'])
            ->assertRedirect(self::ROOT.'/up?utm_source=x&page=2')
            ->assertStatus(301);
    }

    public function test_post_requests_are_never_redirected(): void
    {
        $this->withExceptionHandling();

        $response = $this->request('POST', 'http://www.taketwostudio1603.com/public/no-such-route', ['SCRIPT_NAME' => '/public/index.php']);

        // Whatever the app answers (404 page, 500 without a DB), it must not be a canonical redirect.
        $this->assertNotContains($response->getStatusCode(), [301, 302, 307, 308]);
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_disabled_flag_turns_the_middleware_off(): void
    {
        config(['app.force_canonical' => false]);

        $this->request('GET', 'http://www.taketwostudio1603.com/up')->assertOk();
    }

    public function test_non_https_app_url_disables_redirects_as_a_safety_net(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->request('GET', 'http://www.taketwostudio1603.com/up')->assertOk();
    }
}

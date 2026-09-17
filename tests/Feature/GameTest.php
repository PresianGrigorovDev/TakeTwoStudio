<?php

namespace Tests\Feature;

use App\Filament\Pages\GameStats;
use App\Filament\Resources\GameEventResource;
use App\Filament\Resources\GameEventResource\Widgets\GameOverviewWidget;
use App\Filament\Resources\GameEventResource\Widgets\GameStatsWidget;
use App\Filament\Resources\GameStickerResource;
use App\Filament\Resources\GameStickerResource\Pages\CreateGameSticker;
use App\Filament\Resources\GameStickerResource\Pages\ListGameStickers;
use App\Models\GameEvent;
use App\Models\GameSticker;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\GameVoucher;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Tests\TestCase;

/**
 * QR sticker mini-game: /igra page (noindex, cookie-free, bare layout) and the
 * anonymous event endpoint POST /api/igra/event.
 */
class GameTest extends TestCase
{
    use RefreshDatabase;

    private const EVENT_URL = '/api/igra/event';

    private const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    public static function targets(): array
    {
        return [
            'prom' => ['prom', 'Протокол: Излизане от Матрицата [Варна]'],
            'wedding' => ['wedding', 'Логически пъзел: Сватбената маса'],
        ];
    }

    #[DataProvider('targets')]
    public function test_game_page_renders_for_each_target(string $target, string $title): void
    {
        $response = $this->get("/igra?target={$target}&loc=mg");

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $html = $response->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertMatchesRegularExpression('/<body[^>]*\bdata-target="'.$target.'"/', $html);
        $this->assertStringContainsString('<title>'.$title.' | Take Two Studio 1603</title>', $html);

        foreach (['class="navbar', 'cookieConsentBanner', 'bootstrap.min.css', 'fontawesome', 'aos.css', 'googletagmanager', 'name="csrf-token"', '<style'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "/igra must not contain {$forbidden}");
        }

        $config = $this->configFrom($html);
        $this->assertSame($target, $config['target']);
        $this->assertSame('mg', $config['loc']);
        $this->assertSame(url('/api/igra/event'), $config['eventUrl']);
        $this->assertSame(5, $config['discountPercent']);          // default: 5% at win …
        $this->assertSame(15, $config['discountPercentShared']);   // … 15% after sharing the Story
        $this->assertSame(url($target === 'wedding' ? '/weddings' : '/proms'), $config['useUrl']);
        $this->assertSame(72, $config['validityHours']);
        $this->assertSame('taketwostudio1603', $config['instagramHandle']);
        $this->assertStringStartsWith('https://', $config['instagramUrl']);
        $this->assertStringStartsWith('/', $config['logoUrl']);
        $this->assertStringEndsWith('logo-tts-white.webp', $config['logoUrl']);
        $this->assertSame('Take Two Studio 1603', $config['studioName']);
        $this->assertIsInt($config['seasonYear']);
        $this->assertNull($config['legalUrl']);

        // The reveal logo has no width/height attributes: CSS owns the sizing.
        $this->assertSame(1, preg_match('/<img[^>]*logo-tts-white\.webp[^>]*>/', $html, $img));
        $this->assertStringNotContainsString('width=', $img[0]);
        $this->assertStringNotContainsString('height=', $img[0]);

        $this->assertStringContainsString('igra.css?v=', $html);
        $this->assertStringContainsString('igra.js?v=', $html);
        $this->assertStringContainsString('5% OFF VOUCHER', $html);
        $this->assertStringContainsString('отстъпката става 15%', $html);
        $this->assertStringContainsString('id="rv-use"', $html);
        $this->assertStringContainsString('Използвай кода в сайта', $html);
        $this->assertStringNotContainsString('25%', $html);
    }

    public function test_discount_percents_come_from_site_settings(): void
    {
        SiteSetting::query()->create(['setting_key' => 'game_discount_base_prom', 'setting_value' => '10']);
        SiteSetting::query()->create(['setting_key' => 'game_discount_shared_prom', 'setting_value' => '20']);

        $html = $this->get('/igra?target=prom')->assertOk()->getContent();
        $config = $this->configFrom($html);

        $this->assertSame(10, $config['discountPercent']);
        $this->assertSame(20, $config['discountPercentShared']);
        $this->assertStringContainsString('10% OFF VOUCHER', $html);
        $this->assertStringContainsString('отстъпката става 20%', $html);

        // The other game keeps its defaults.
        $this->assertSame(5, $this->configFrom($this->get('/igra?target=wedding')->getContent())['discountPercent']);
    }

    public function test_voucher_event_is_unique_per_code_and_sharing_boosts_the_discount(): void
    {
        // The voucher row is created once (the browser may retry the beacon) and freezes both levels.
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'voucher', 'code' => 'VN-PROM-K7M3'])->assertNoContent();
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'voucher', 'code' => 'VN-PROM-K7M3'])->assertNoContent();
        $this->assertSame(1, GameEvent::query()->where('event', 'voucher')->count());

        $voucher = GameEvent::query()->where('event', 'voucher')->firstOrFail();
        $this->assertSame(5, $voucher->meta['percent']);
        $this->assertSame(15, $voucher->meta['percent_shared']);
        $this->assertArrayNotHasKey('boosted_at', $voucher->meta);

        // A preview-only share does not unlock the higher discount …
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'share', 'code' => 'VN-PROM-K7M3', 'meta' => ['method' => 'preview']])->assertNoContent();
        $this->assertSame(5, $voucher->fresh()->meta['percent']);

        // … but a real share (share sheet / download / manual confirmation) does, once.
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'share', 'code' => 'VN-PROM-K7M3', 'meta' => ['method' => 'share']])->assertNoContent();
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'share', 'code' => 'VN-PROM-K7M3', 'meta' => ['method' => 'confirm']])->assertNoContent();
        $boosted = $voucher->fresh();
        $this->assertSame(15, $boosted->meta['percent']);
        $this->assertNotEmpty($boosted->meta['boosted_at']);

        // "Използвай кода в сайта" is logged as its own event.
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'use', 'code' => 'VN-PROM-K7M3'])->assertNoContent();
        $this->assertSame(1, GameEvent::query()->where('event', 'use')->count());
        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'event' => 'use'])->assertStatus(422);   // code required
    }

    public function test_invalid_or_missing_target_defaults_to_prom(): void
    {
        foreach (['/igra', '/igra?target=hack', '/igra?target[]=x', '/igra?target='] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<body[^>]*\bdata-target="prom"/', $html, $path);
            $this->assertSame('prom', $this->configFrom($html)['target'], $path);
        }

        $this->assertSame('wedding', $this->configFrom($this->get('/igra?target=%20WEDDING%20')->getContent())['target']);
    }

    public static function locs(): array
    {
        return [
            'spaces and punctuation' => ['Morska Gradina!', 'morska-gradina'],
            'upper case' => ['MG', 'mg'],
            'empty' => ['', null],
            'too long' => [str_repeat('a', 50), str_repeat('a', 40)],
            'only punctuation' => ['!!!', null],
        ];
    }

    #[DataProvider('locs')]
    public function test_loc_is_sanitized(string $raw, ?string $expected): void
    {
        $html = $this->get('/igra?target=prom&loc='.urlencode($raw))->assertOk()->getContent();
        $config = $this->configFrom($html);

        $this->assertSame($expected, $config['loc']);

        if ($expected !== null) {
            $this->assertMatchesRegularExpression(GameEvent::LOC_PATTERN, $config['loc']);
        }
    }

    public function test_loc_cannot_break_out_of_the_config_script_tag(): void
    {
        $html = $this->get('/igra?target=prom&loc='.urlencode('</script><script>alert(1)</script>'))->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertMatchesRegularExpression(GameEvent::LOC_PATTERN, $this->configFrom($html)['loc']);
    }

    public function test_event_is_stored_and_returns_204(): void
    {
        $this->postJson(self::EVENT_URL, [
            'target' => 'prom',
            'loc' => 'mg',
            'event' => 'voucher',
            'code' => 'VN-PROM-K7M3',
            'meta' => ['lives_left' => '2', 'seconds' => 95],
        ], ['User-Agent' => self::IPHONE_UA])->assertNoContent();

        $this->assertDatabaseCount('game_events', 1);

        $event = GameEvent::query()->firstOrFail();
        $this->assertSame('prom', $event->target);
        $this->assertSame('mg', $event->loc);
        $this->assertSame('voucher', $event->event);
        $this->assertSame('VN-PROM-K7M3', $event->code);
        // Voucher rows additionally freeze both discount levels the player was shown.
        $this->assertSame(['lives_left' => 2, 'seconds' => 95, 'device' => 'mobile', 'percent' => 5, 'percent_shared' => 15], $event->meta);
        $this->assertNotNull($event->created_at);
        $this->assertNull($event->redeemed_at);

        foreach (['ip', 'ip_address', 'user_agent', 'user_id', 'updated_at'] as $column) {
            $this->assertFalse(Schema::hasColumn('game_events', $column), "game_events must not store {$column}");
        }

        // No client hint, desktop UA -> desktop.
        $this->postJson(self::EVENT_URL, ['target' => 'wedding', 'event' => 'scan'])->assertNoContent();
        $this->assertSame('desktop', GameEvent::query()->where('event', 'scan')->firstOrFail()->meta['device']);

        // Client hint wins over the UA string.
        $this->postJson(self::EVENT_URL, ['target' => 'wedding', 'event' => 'lose', 'meta' => ['lives_left' => 0]], ['Sec-CH-UA-Mobile' => '?1'])->assertNoContent();
        $this->assertSame('mobile', GameEvent::query()->where('event', 'lose')->firstOrFail()->meta['device']);
    }

    public function test_event_accepts_text_plain_json_body(): void
    {
        // navigator.sendBeacon fallback: JSON in a text/plain body.
        $body = json_encode(['target' => 'wedding', 'loc' => 'morska', 'event' => 'share', 'code' => 'VN-WED-A2B3', 'meta' => ['method' => 'share']]);

        $this->call('POST', self::EVENT_URL, [], [], [], ['CONTENT_TYPE' => 'text/plain', 'HTTP_ACCEPT' => '*/*'], $body)
            ->assertNoContent();

        $this->assertDatabaseHas('game_events', ['target' => 'wedding', 'loc' => 'morska', 'event' => 'share', 'code' => 'VN-WED-A2B3']);
        $this->assertSame('share', GameEvent::query()->firstOrFail()->meta['method']);
    }

    public static function invalidPayloads(): array
    {
        $voucher = ['target' => 'prom', 'loc' => 'mg', 'event' => 'voucher', 'code' => 'VN-PROM-K7M3'];

        return [
            'unknown target' => [['target' => 'hack', 'event' => 'scan']],
            'missing target' => [['event' => 'scan']],
            'unknown event' => [['target' => 'prom', 'event' => 'click']],
            'loc with space' => [['target' => 'prom', 'loc' => 'morska gradina', 'event' => 'scan']],
            'loc too long' => [['target' => 'prom', 'loc' => str_repeat('a', 41), 'event' => 'scan']],
            'code too short' => [array_replace($voucher, ['code' => 'VN-PROM-12'])],
            'code lower case' => [array_replace($voucher, ['code' => 'vn-prom-abcd'])],
            'code with ambiguous 0' => [array_replace($voucher, ['code' => 'VN-PROM-ABC0'])],
            'code on scan' => [['target' => 'prom', 'event' => 'scan', 'code' => 'VN-PROM-K7M3']],
            'voucher without code' => [['target' => 'prom', 'event' => 'voucher']],
            'share without code' => [['target' => 'prom', 'event' => 'share', 'meta' => ['method' => 'share']]],
            'wedding code on prom' => [['target' => 'prom', 'event' => 'voucher', 'code' => 'VN-WED-K7M3']],
            'prom code on wedding' => [['target' => 'wedding', 'event' => 'voucher', 'code' => 'VN-PROM-K7M3']],
            'unknown meta key' => [['target' => 'prom', 'event' => 'win', 'meta' => ['email' => 'x@example.com']]],
            'lives_left out of range' => [['target' => 'prom', 'event' => 'win', 'meta' => ['lives_left' => 9]]],
            'unknown share method' => [['target' => 'prom', 'event' => 'share', 'code' => 'VN-PROM-K7M3', 'meta' => ['method' => 'email']]],
            'meta as string' => [['target' => 'prom', 'event' => 'win', 'meta' => 'lives_left=2']],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_event_rejects_invalid_payloads(array $payload): void
    {
        $this->postJson(self::EVENT_URL, $payload)
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);

        // A plain (non-JSON Accept) request must also get a 422, never a 302 redirect.
        $this->post(self::EVENT_URL, $payload)->assertStatus(422);

        $this->assertDatabaseCount('game_events', 0);
    }

    public function test_event_caps_payload_size(): void
    {
        $body = json_encode(['target' => 'prom', 'event' => 'scan', 'meta' => ['method' => str_repeat('a', 5 * 1024)]]);

        $this->call('POST', self::EVENT_URL, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
            ->assertStatus(413);

        $this->assertDatabaseCount('game_events', 0);
    }

    public function test_event_is_rate_limited_per_ip(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'scan'])
                ->assertNoContent();
        }

        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'scan'])->assertStatus(429);

        $this->assertDatabaseCount('game_events', 120);
    }

    public function test_event_endpoint_is_csrf_exempt_and_route_has_throttle(): void
    {
        $neverVerify = new ReflectionProperty(ValidateCsrfToken::class, 'neverVerify');
        $this->assertContains('api/igra/*', $neverVerify->getValue());

        $event = Route::getRoutes()->getByName('game.event');
        $this->assertNotNull($event);
        $this->assertSame(['POST'], $event->methods());
        $this->assertSame('api/igra/event', $event->uri());
        $this->assertContains('throttle:120,1', $event->gatherMiddleware());

        // Cookie-free group: no session, no CSRF, no shared $errors on either route.
        foreach (['game.show', 'game.event'] as $name) {
            $excluded = Route::getRoutes()->getByName($name)->excludedMiddleware();
            $this->assertContains(StartSession::class, $excluded, $name);
            $this->assertContains(ValidateCsrfToken::class, $excluded, $name);
            $this->assertContains(ShareErrorsFromSession::class, $excluded, $name);
        }

        $this->assertSame('igra', Route::getRoutes()->getByName('game.show')->uri());
    }

    public function test_game_routes_set_no_cookies(): void
    {
        $this->get('/igra?target=prom&loc=mg')->assertOk()->assertHeaderMissing('Set-Cookie');

        $this->postJson(self::EVENT_URL, ['target' => 'prom', 'loc' => 'mg', 'event' => 'scan'])
            ->assertNoContent()
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_game_page_is_not_in_sitemap_or_nav(): void
    {
        $this->seed();

        $sitemap = $this->get('/sitemap-pages.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('/igra', $sitemap);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('href="/igra', $home);
        $this->assertStringNotContainsString('href="'.url('/igra'), $home);
    }

    public function test_public_igra_directory_must_not_exist(): void
    {
        // The root .htaccess returns 403 for URLs that resolve to a real directory, which would kill /igra.
        $this->assertDirectoryDoesNotExist(public_path('igra'));
    }

    public function test_admin_can_browse_game_events_and_marketing_stats_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        GameEvent::create(['target' => 'prom', 'loc' => 'mg', 'event' => 'scan', 'meta' => ['device' => 'mobile']]);
        GameEvent::create(['target' => 'prom', 'loc' => 'mg', 'event' => 'win', 'meta' => ['lives_left' => 3, 'device' => 'mobile']]);
        GameEvent::create(['target' => 'prom', 'loc' => 'mg', 'event' => 'voucher', 'code' => 'VN-PROM-K7M3', 'meta' => ['device' => 'mobile']]);

        $this->actingAs($admin)
            ->get(GameEventResource::getUrl())
            ->assertOk()
            ->assertSee('VN-PROM-K7M3')
            ->assertSee('Маркирай като използван');

        // Both entries live in the "Маркетинг" navigation group.
        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('QR Игра – статистика')
            ->assertSee('QR Игра – събития');

        // Dedicated statistics page (Маркетинг → QR Игра – статистика): no hardcoded links,
        // scanned locations are listed and can be turned into stickers.
        $this->actingAs($admin)
            ->get(GameStats::getUrl())
            ->assertOk()
            ->assertSee('QR Игра – статистика')
            ->assertSee('Как да валидираш код от Instagram DM')
            ->assertSee('Всички събития и кодове')
            ->assertSee('QR стикери')
            ->assertSee('Сканирани локации')
            ->assertSee('mg')
            ->assertSee('Създай стикер')
            ->assertDontSee('без локация');

        // The QR generator scripts are registered on the panel (every admin page) and exist on disk.
        $page = $this->actingAs($admin)->get(GameStats::getUrl())->getContent();
        $this->assertStringContainsString('vendor/qrcode/qrcode.min.js', $page);
        $this->assertStringContainsString('js/admin/game-qr.js?v=', $page);
        $this->assertSame(1, substr_count($page, 'js/admin/game-qr.js'), 'QR generator script must be loaded exactly once');
        // Our scripts must run before Filament's Alpine bundle so x-data="GameQr.panel(...)" can resolve.
        $this->assertLessThan(strpos($page, 'js/filament/filament/app.js'), strpos($page, 'js/admin/game-qr.js'));
        foreach (['vendor/qrcode/qrcode.min.js', 'vendor/qrcode/LICENSE', 'js/admin/game-qr.js', 'css/img/logo-tts-white.webp', 'css/img/logo-tts-black.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }

        // The dashboard must NOT carry the game widgets any more (they moved to the Маркетинг page);
        // Livewire mounts widgets by their kebab-case component alias, so its absence proves they are not registered there.
        $dashboard = $this->actingAs($admin)->get('/admin')->getContent();
        $this->assertStringNotContainsString('game-stats-widget', $dashboard);
        $this->assertStringNotContainsString('game-overview-widget', $dashboard);

        // Header widgets are lazy Livewire components, so their content is asserted on the components themselves.
        Livewire::actingAs($admin)
            ->test(GameStatsWidget::class)
            ->assertSee('QR Игра – статистика по локация')
            ->assertSee('Абитуриентски бал')
            ->assertSee('mg')
            ->assertSee('100% · 100%')
            ->assertSee('Провери код от DM');

        Livewire::actingAs($admin)
            ->test(GameOverviewWidget::class)
            ->assertSee('Сканирания')
            ->assertSee('Активни ваучери (72 ч)')
            ->assertSee('Използвани ваучери')
            ->assertSee('1 издадени общо');

        // 1 scan / 1 win / 1 voucher for prom+mg -> both conversions are 100%.
        $stats = app(GameStatsWidget::class)->getStats();
        $this->assertSame(['prom'], array_keys($stats));
        $this->assertSame(1, $stats['prom']['rows'][0]['scans']);
        $this->assertSame('mg', $stats['prom']['rows'][0]['loc']);
        $this->assertSame(100, $stats['prom']['totals']['win_rate']);
        $this->assertSame(100, $stats['prom']['totals']['voucher_rate']);
    }

    public function test_admin_creates_qr_stickers_with_generated_links_and_qr_codes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Create: the loc is sanitized like the public page and must be unique per game.
        Livewire::actingAs($admin)
            ->test(CreateGameSticker::class)
            ->fillForm(['name' => 'Морска градина – главен вход', 'target' => 'prom', 'loc' => 'Morska Gradina!'])
            ->call('create')
            ->assertHasNoFormErrors();

        $sticker = GameSticker::query()->firstOrFail();
        $this->assertSame('morska-gradina', $sticker->loc);
        $this->assertSame('prom', $sticker->target);
        $this->assertSame(url('/igra?target=prom&loc=morska-gradina'), $sticker->url);

        Livewire::actingAs($admin)
            ->test(CreateGameSticker::class)
            ->fillForm(['name' => 'Дубликат', 'target' => 'prom', 'loc' => 'morska-gradina'])
            ->call('create')
            ->assertHasFormErrors(['loc']);

        // The same loc is allowed for the other game (different QR, different link).
        Livewire::actingAs($admin)
            ->test(CreateGameSticker::class)
            ->fillForm(['name' => 'Морска градина (сватби)', 'target' => 'wedding', 'loc' => 'morska-gradina'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseCount('game_stickers', 2);

        // Prefill from the statistics page: ?target=&loc= land in the create form.
        Livewire::actingAs($admin)
            ->withQueryParams(['target' => 'wedding', 'loc' => 'Sevastopol'])
            ->test(CreateGameSticker::class)
            ->assertFormSet(['target' => 'wedding', 'loc' => 'sevastopol']);

        // Events with the same target + loc are counted on the sticker; the QR modal renders the generator.
        GameEvent::create(['target' => 'prom', 'loc' => 'morska-gradina', 'event' => 'scan']);
        GameEvent::create(['target' => 'prom', 'loc' => 'morska-gradina', 'event' => 'scan']);
        GameEvent::create(['target' => 'prom', 'loc' => 'morska-gradina', 'event' => 'win']);
        GameEvent::create(['target' => 'wedding', 'loc' => 'morska-gradina', 'event' => 'scan']);

        $counted = GameSticker::query()->withEventCounts()->whereKey($sticker->id)->firstOrFail();
        $this->assertSame(2, (int) $counted->scans);
        $this->assertSame(1, (int) $counted->wins);
        $this->assertSame(0, (int) $counted->vouchers);

        Livewire::actingAs($admin)
            ->test(ListGameStickers::class)
            ->assertCanSeeTableRecords(GameSticker::all())
            ->assertSee('Морска градина – главен вход')
            ->assertSee('morska-gradina')
            ->mountTableAction('qr', $sticker)
            ->assertSee('GameQr.panel(')
            ->assertSee('Свали PNG')
            ->assertSee('Свали SVG');

        // The statistics page links a scanned location to its sticker.
        $this->actingAs($admin)
            ->get(GameStats::getUrl())
            ->assertOk()
            ->assertSee('Морска градина – главен вход')
            ->assertSee(GameStickerResource::getUrl('edit', ['record' => $sticker]));

        // Deleting a sticker never deletes its events.
        $sticker->delete();
        $this->assertDatabaseCount('game_stickers', 1);
        $this->assertDatabaseCount('game_events', 4);
    }

    public function test_admin_edits_the_game_discounts_on_the_stats_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(GameStats::class)
            ->assertFormSet(['prom_base' => 5, 'prom_shared' => 15, 'wedding_base' => 5, 'wedding_shared' => 15])
            ->fillForm(['prom_base' => 8, 'prom_shared' => 20, 'wedding_base' => 5, 'wedding_shared' => 12])
            ->call('saveSettings')
            ->assertHasNoFormErrors();

        $this->assertSame(8, GameVoucher::percentFor('prom', 'base'));
        $this->assertSame(20, GameVoucher::percentFor('prom', 'shared'));
        $this->assertSame(5, GameVoucher::percentFor('wedding', 'base'));
        $this->assertSame(12, GameVoucher::percentFor('wedding', 'shared'));
        $this->assertSame('20', SiteSetting::query()->where('setting_key', 'game_discount_shared_prom')->value('setting_value'));

        // The shared discount can never be lower than the base one.
        Livewire::actingAs($admin)
            ->test(GameStats::class)
            ->fillForm(['prom_base' => 20, 'prom_shared' => 10])
            ->call('saveSettings')
            ->assertHasFormErrors(['prom_shared']);
    }

    /** @return array<string,mixed> */
    private function configFrom(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/json" id="igra-config">(.*?)</script>#s', $html, $m), 'igra-config JSON tag missing');

        $config = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($config);

        return $config;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\GameEvent;
use App\Models\LegalPage;
use App\Support\Seo\PromSeason;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * QR sticker mini-game (/igra?target=prom|wedding&loc=<sticker>).
 *
 * The page is noindex, cookie-free and shares nothing with the main layout.
 * The browser generates the voucher code and posts anonymous events to
 * /api/igra/event (fire-and-forget, also via navigator.sendBeacon as text/plain).
 * Both routes run without StartSession / CSRF (see routes/web.php).
 */
class GameController extends Controller
{
    public const DISCOUNT_PERCENT = 15;

    public const VALIDITY_HOURS = 72;

    public const MAX_BODY_BYTES = 4096;

    public const DEFAULT_INSTAGRAM_URL = 'https://www.instagram.com/taketwostudio1603';

    public const DEFAULT_INSTAGRAM_HANDLE = 'taketwostudio1603';

    /** Root-relative on purpose: an absolute URL on a different host would taint the Story canvas. */
    public const LOGO_URL = '/css/img/logo-tts-white.webp';

    /** @var array<string,array{title:string,description:string,theme:string}> */
    public const PAGE = [
        'prom' => [
            'title' => 'Протокол: Излизане от Матрицата [Варна]',
            'description' => 'Мини игра от QR стикер във Варна: открий четирите скрити връзки за 4 опита и вземи '.self::DISCOUNT_PERCENT.'% отстъпка за фото и видео заснемане на бала от Take Two Studio 1603.',
            'theme' => '#050807',
        ],
        'wedding' => [
            'title' => 'Логически пъзел: Сватбената маса',
            'description' => 'Логически пъзел от QR стикер: настани шестимата гости според четирите правила и вземи '.self::DISCOUNT_PERCENT.'% отстъпка за сватбено фото и видео от Take Two Studio 1603.',
            'theme' => '#0b0b0f',
        ],
    ];

    public function show(Request $request): Response
    {
        $target = $this->sanitizeTarget($request->query('target'));
        $loc = $this->sanitizeLoc($request->query('loc'));

        $instagramUrl = Settings::socialLinks()['instagram'] ?? self::DEFAULT_INSTAGRAM_URL;

        // The legal page is optional: link it only when the route exists and the row is published.
        $legalUrl = Route::has('legal.igra')
            && LegalPage::query()->where('slug', 'igra-usloviya')->where('is_published', true)->exists()
            ? route('legal.igra')
            : null;

        $config = [
            'target' => $target,
            'loc' => $loc,
            'eventUrl' => route('game.event'),
            'instagramHandle' => $this->instagramHandle($instagramUrl),
            'instagramUrl' => $instagramUrl,
            'logoUrl' => self::LOGO_URL,
            'studioName' => Settings::siteName(),
            'discountPercent' => self::DISCOUNT_PERCENT,
            'validityHours' => self::VALIDITY_HOURS,
            'seasonYear' => PromSeason::year(),
            'legalUrl' => $legalUrl,
        ];

        return response()
            ->view('game.index', [
                'target' => $target,
                'loc' => $loc,
                'config' => $config,
                'page' => self::PAGE[$target],
                // loc is deliberately left out of the URL people share.
                'shareUrl' => route('game.show', ['target' => $target]),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function event(Request $request): Response|JsonResponse
    {
        $raw = $request->getContent();

        if (strlen($raw) > self::MAX_BODY_BYTES) {
            return response()->json(['message' => 'Payload too large'], 413);
        }

        // navigator.sendBeacon can only send text/plain, so also accept JSON in the raw body.
        $input = $request->all();
        if (! $request->isJson()) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && $decoded !== []) {
                $input = $decoded;
            }
        }

        // Explicit Validator: sendBeacon sends Accept: */*, so $request->validate() would answer with a 302.
        $validator = Validator::make($input, [
            'target' => ['required', 'string', Rule::in(GameEvent::TARGETS)],
            'loc' => ['nullable', 'string', 'regex:'.GameEvent::LOC_PATTERN],
            'event' => ['required', 'string', Rule::in(GameEvent::EVENTS)],
            'code' => ['nullable', 'string', 'regex:'.GameEvent::CODE_PATTERN, 'required_if:event,voucher,share', 'prohibited_unless:event,voucher,share'],
            'meta' => ['nullable', 'array:lives_left,seconds,attempts,solved,method'],
            'meta.lives_left' => ['nullable', 'integer', 'between:0,4'],
            'meta.seconds' => ['nullable', 'integer', 'between:0,86400'],
            'meta.attempts' => ['nullable', 'integer', 'between:0,999'],
            'meta.solved' => ['nullable', 'integer', 'between:0,4'],
            'meta.method' => ['nullable', 'string', Rule::in(['share', 'download', 'preview'])],
        ]);

        // A VN-WED-… code on the prom game (or vice versa) is a forged payload.
        $validator->after(function ($validator) use ($input): void {
            $code = $input['code'] ?? null;
            $target = $input['target'] ?? null;

            if (is_string($code) && is_string($target) && in_array($target, GameEvent::TARGETS, true)
                && ! str_starts_with($code, 'VN-'.GameEvent::prefixFor($target).'-')) {
                $validator->errors()->add('code', 'Prefix/target mismatch');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $meta = array_filter($data['meta'] ?? [], fn ($value) => $value !== null && $value !== '');
        foreach (['lives_left', 'seconds', 'attempts', 'solved'] as $key) {
            if (isset($meta[$key])) {
                $meta[$key] = (int) $meta[$key];
            }
        }
        $meta['device'] = self::deviceClass($request);

        GameEvent::create([
            'target' => $data['target'],
            'loc' => $data['loc'] ?? null,
            'event' => $data['event'],
            'code' => $data['code'] ?? null,
            'meta' => $meta,
        ]);

        return response()->noContent();
    }

    /** Anything that is not exactly prom|wedding (including ?target[]=x) falls back to prom. */
    private function sanitizeTarget(mixed $raw): string
    {
        if (! is_string($raw)) {
            return 'prom';
        }

        $target = strtolower(trim($raw));

        return in_array($target, GameEvent::TARGETS, true) ? $target : 'prom';
    }

    /** Sticker slug: lower-case latin letters, digits and dashes, max 40 chars, or null. */
    private function sanitizeLoc(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $loc = trim(mb_substr(Str::slug(Str::lower($raw)), 0, 40), '-');

        return $loc === '' ? null : $loc;
    }

    /** "https://www.instagram.com/taketwostudio1603/" -> "taketwostudio1603" */
    private function instagramHandle(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $handle = ltrim(explode('/', $path)[0], '@');

        return $handle !== '' ? $handle : self::DEFAULT_INSTAGRAM_HANDLE;
    }

    /**
     * "mobile" or "desktop" from the Client Hints header, else from the UA string.
     * The UA is only inspected here and never stored. iPadOS reports a Mac UA and
     * therefore counts as desktop (known limitation).
     */
    private static function deviceClass(Request $request): string
    {
        $hint = $request->header('Sec-CH-UA-Mobile');

        if ($hint === '?1') {
            return 'mobile';
        }

        if ($hint === '?0') {
            return 'desktop';
        }

        return preg_match('/Mobi|Android|iPhone|iPad|iPod/i', (string) $request->userAgent()) ? 'mobile' : 'desktop';
    }
}

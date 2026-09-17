# Игра „The Mystery Protocol“ (`/igra`) – план за имплементация

Дата: 2026-09-17 · Проект: TakeTwoStudio (Laravel 12, Filament 3, cPanel/LiteSpeed, без Node на сървъра)

## 1. Контекст и цел

QR стикери из Варна водят към лека мобилна уеб игра на сайта на Take Two Studio 1603. Според `?target=` се зарежда една от две игри: „The Prom Connections“ (NYT Connections стил, 4×4, 4 живота) за абитуриенти или „The Seating Chart Nightmare“ (кръгла маса, 6 стола, 4 логически правила) за сватби. При победа играчът вижда името/логото на студиото, получава код за 15% отстъпка (генериран в браузъра, формат `VN-PROM-XXXX` / `VN-WED-XXXX`, валиден 72 часа) и може да генерира 1080×1920 Instagram Story артефакт от Canvas, който сваля или споделя, след което праща кода на лично съобщение в Instagram. Целта е лийдове за бал (приоритет 1) и сватби (приоритет 2) с нулеви промени в останалия сайт и без бисквитки/лични данни.

Публичните страници на сайта **не** ползват Tailwind/Vite (само self-hosted CSS/JS от `public/`), статична папка `public/igra/` не може да се сервира като `/igra` (root `.htaccess` връща 403 за директории), затова играта е Blade маршрут със собствен „гол“ layout и два статични файла `public/css/igra.css` + `public/js/igra.js`.

## 2. Взети решения

Решения на собственика (2026-09-17):
- **D1 Ваучер само в браузъра.** Кодът се генерира в JS, не се записва в `promo_codes`, няма сървърна валидация. Студиото валидира ръчно при DM чрез справка в админа (таблица `game_events`). Кодовете **не** работят в калкулаторите на сайта и в `/api/validate-promo-code` – текстовете не обещават онлайн използване.
- **D2 Отстъпка 15%** и за бал, и за сватба. Бадж „15% OFF VOUCHER“. Никъде „25%“.
- **D3 Проследяване** в собствена таблица `game_events` (target, loc, event, code, meta, created_at) без IP, без UA, без бисквитки. Filament ресурс (read-only + действие „Маркирай като използван“) и widget със статистика по локация.
- **D4 Страница** `GET /igra?target=prom|wedding&loc=<slug>` с нов layout `layouts/game.blade.php`: без навигация, футър, cookie банер, промо попъп, sticky CTA, lead modal, аналитика. `noindex,nofollow`, не е в sitemap и в навигацията (и двете са хардкоднати списъци).
- **D5 Палитра.** Бал: тъмно `#050807` + matrix зелено `#39ff14` / циан `#00e5ff`, злато `#D4AF37` само на победния екран. Сватба: noir `#0b0b0f` + злато `#D4AF37` / шампанско `#f1e3b5`.
- **D6 Шрифтове.** Self-hosted Montserrat (има кирилица, `public/fonts/montserrat/`) за UI и canvas + системен monospace stack за „терминалните“ акценти. Без нови шрифтови файлове.
- **D7 Cache busting** с `asset('css/igra.css').'?v='.filemtime(...)` (`public/.htaccess` кешира css/js 1 месец; хеширани имена изискват build стъпка, която cPanel няма).
- **D8 Пъзел за масата – проверка по правила.** Има точно 4 валидни решения (две огледални двойки); всяко валидно нареждане печели; правилата светят `[OK]`/`[ X]` едно по едно.
- **D9 Победен екран** само с текста за разкриване, лого, код, Story генератор, 3 стъпки и линк към Instagram профила. Без форми.

Технически решения (сглобени от трите дизайн-перспективи; при разминаване е избрано следното):
- **Throttle на `POST /api/igra/event` = `throttle:120,1`** (не 60). Laravel ключува по IP; цял клас зад един NAT/CGNAT (училищен Wi-Fi, A1/Yettel) е един „клиент“. ≤4 събития на играч → 120/мин покрива ~30 едновременни играчи. 429 = загуба само на статистика, никога на геймплей.
- **CSRF: изключение `api/igra/*`** в `bootstrap/app.php` (`validateCsrfTokens(except:)`). Endpoint-ът няма сесийна „власт“ за подправяне; токен би умрял със 120-мин сесия, докато QR страницата стои отворена часове; `sendBeacon` не може да праща header-и; тестът `PagesRenderTest` забранява csrf meta тага.
- **Cookie-free маршрути (препоръчително, 3 реда).** Двата маршрута в група `Route::withoutMiddleware([StartSession, ShareErrorsFromSession, ValidateCsrfToken])`, за да не се сетват `laravel_session`/`XSRF-TOKEN` → твърдението „без бисквитки“ е буквално вярно и страницата е кешируема. Ограничение: Blade-ът на играта не бива да вика `session()`, `old()`, `$errors`, `csrf_field()` (пазено с тест `assertHeaderMissing('Set-Cookie')`).
- **Код на ваучера без контролна цифра:** `VN-PROM-` + 1 дата-символ + 3 случайни от 32-символна азбука без `0/O/1/I` (32 768 комбинации на 3-дневен период). Истинската валидация е справката в админа; контролна цифра, която всеки може да прочете от JS, само наполовина би намалила пространството. Сървърът проверява само regex + съответствие префикс↔target.
- **Story flow на две стъпки:** „Генерирай Story Артефакт“ (async: шрифтове → лого → canvas → blob → 9:16 превю) → синхронен „Сподели“ (`navigator.share({files})`) или „Свали артефакта“ (`a[download]`). Пре-рендерът стартира при показване на победния екран. iOS Safari изисква `share()` да е вътре в жеста; in-app браузъри (Instagram/Facebook/Viber) получават превюто + „Задръж снимката, за да я запазиш.“
- **Без `maximum-scale=1` във viewport-а** (WCAG 1.4.4; Android Chrome блокира pinch-zoom за хора с намалено зрение, iOS го игнорира). Двойният тап се неутрализира с `touch-action: manipulation`.
- **`logoUrl` е root-relative** (`/css/img/logo-tts-white.webp`), не `asset()` – при разминаване на host (dev `127.0.0.1` vs `localhost`) абсолютният URL прави canvas-а „tainted“ и `toBlob` хвърля `SecurityError`.
- **Колона `redeemed_at`** (nullable) в `game_events` + Filament действие „Маркирай като използван“ само за редове `event=voucher` – контролът „един код – едно използване“ за ръчния DM процес.
- **Изпращане на събития:** `fetch(..., {keepalive:true})` първи, `navigator.sendBeacon` с `text/plain` Blob като fallback; контролерът чете JSON и от `application/json`, и от raw body. Fire-and-forget, никога не блокира играта.
- **Filament показва часове в `Europe/Sofia`** чрез `->dateTime('d.m.Y H:i', 'Europe/Sofia')`; `config('app.timezone')` остава `UTC` (ползва се от резервациите/sitemap).

## 3. Файлове

| Път | Действие | Какво |
|---|---|---|
| `database/migrations/2026_09_17_000000_create_game_events_table.php` | create | Таблица `game_events` (виж 4.1) |
| `app/Models/GameEvent.php` | create | Модел: `UPDATED_AT = null`, константи TARGETS/EVENTS/LOC_PATTERN/CODE_PATTERN, `meta` array cast |
| `app/Support/Assets.php` | create | `Assets::versioned('css/igra.css')` → `asset().'?v='.filemtime` с `is_file()` guard |
| `app/Http/Controllers/GameController.php` | create | `show()` (target/loc санитизация, `$config`, `X-Robots-Tag`), `event()` (валидация, device class, insert, 204) |
| `routes/web.php` | modify | `GET /igra` (`game.show`), `POST /api/igra/event` (`game.event`, `throttle:120,1`), cookie-free група; опц. `GET /igra-usloviya` |
| `bootstrap/app.php` | modify | `$middleware->validateCsrfTokens(except: ['api/igra/*'])` |
| `resources/views/layouts/game.blade.php` | create | Гол layout: собствен `<head>`, `body[data-target]`, `#igra-config` JSON, `igra.js` defer |
| `resources/views/game/index.blade.php` | create | Екрани intro / game / reveal, aria-live, noscript, скрит canvas |
| `resources/views/game/partials/connections.blade.php` | create | Рамка на Игра А (животи, grid контейнер, бутони, lose панел) |
| `resources/views/game/partials/seating.blade.php` | create | Рамка на Игра Б (правила `<details>`, маса + 6 стола, поднос с гости, бутони) |
| `public/css/igra.css` | create | Всички стилове (≤ ~16 KB), токени по `body[data-target]`, keyframes, responsive, a11y |
| `public/js/igra.js` | create | Един IIFE (≤ ~30 KB): Store, Events, Sound, Connections, Seating, Voucher, Reveal, Story, App |
| `app/Filament/Resources/GameEventResource.php` + `GameEventResource/Pages/ListGameEvents.php` | create | Read-only таблица, група „Маркетинг“, търсене по код, филтри, действие „Маркирай като използван“ |
| `app/Filament/Pages/GameStats.php` + `resources/views/filament/pages/game-stats.blade.php` | create | Страница „QR Игра – статистика“ в група „Маркетинг“ (header widgets, сканирани локации, инструкция за валидация на код) |
| `database/migrations/2026_09_18_000000_create_game_stickers_table.php` + `app/Models/GameSticker.php` | create | Стикери (target + loc), броячи от `game_events` |
| `app/Filament/Resources/GameStickerResource.php` + `GameStickerResource/Pages/*` | create | „QR стикери“: CRUD, линк, QR модал |
| `public/vendor/qrcode/qrcode.min.js` (+ LICENSE), `public/js/admin/game-qr.js`, `resources/views/filament/partials/game-qr-panel.blade.php`, `resources/views/filament/game-stickers/qr.blade.php` | create | QR генератор в браузъра (стилове, „?“/лого център, PNG/SVG) |
| `app/Providers/AppServiceProvider.php` | modify | Регистрира двата QR скрипта за админ панела (`FilamentAsset::register`) |
| `tests/TestCase.php` | modify | Нулира статичния Livewire флаг за инжектиране на assets между тестовете |
| `app/Filament/Resources/GameEventResource/Widgets/GameOverviewWidget.php` | create | 4 обобщени карти (сканирания, победи, активни ваучери 72 ч, използвани ваучери) |
| `app/Filament/Resources/GameEventResource/Widgets/GameStatsWidget.php` + `resources/views/filament/widgets/game-stats.blade.php` | create | Статистика по target/loc: сканирания, победи, загуби, ваучери, споделяния, конверсия % |
| `tests/Feature/GameTest.php` | create | ~13 feature теста (виж 4.9) |
| `tests/Feature/PagesRenderTest.php` | modify | `['/igra?target=prom'], ['/igra?target=wedding']` в `pages()` |
| `database/seeders/GameLegalPageSeeder.php` (+ `DatabaseSeeder`) | create (опц.) | `firstOrCreate` ред `igra-usloviya` в `legal_pages` |
| `docs/IGRA-PLAN.md` | create | Този план (копие) |

Референтни файлове за конвенции (само за четене): `app/Http/Controllers/SitePageController.php:155-182` (контролер), `app/Filament/Resources/ActivityLogResource.php` (read-only ресурс), `app/Filament/Widgets/PromoCodeStatsWidget.php` + `resources/views/filament/widgets/promo-code-stats.blade.php` (widget), `resources/views/partials/promo-popup.blade.php:128-157` (countdown), `resources/views/partials/promo-code-input.blade.php:54-62` (fetch), `tests/Feature/PagesRenderTest.php` (забрани за HTML).

## 4. Backend – стъпки

### 4.1 Миграция `create_game_events_table`
```php
Schema::create('game_events', function (Blueprint $table) {
    $table->id();
    $table->string('target', 10);               // prom | wedding
    $table->string('loc', 40)->nullable();      // санитизиран slug на стикера (mg, morska …)
    $table->string('event', 20);                // scan | win | lose | voucher | share
    $table->string('code', 20)->nullable();     // VN-PROM-XXXX / VN-WED-XXXX (генериран в браузъра)
    $table->json('meta')->nullable();           // lives_left, seconds, attempts, solved, method, device
    $table->timestamp('redeemed_at')->nullable();
    $table->timestamp('created_at')->nullable()->useCurrent();
    $table->index(['target', 'loc', 'event']);
    $table->index('code');
    $table->index('created_at');
});
```
Без `updated_at`, IP, UA, user_id. JSON колона има прецедент (`activity_logs.properties`). `down()` = `dropIfExists`.

### 4.2 Модел `App\Models\GameEvent`
- `public const UPDATED_AT = null;`
- `TARGETS = ['prom','wedding']`, `EVENTS = ['scan','win','lose','voucher','share']`
- `LOC_PATTERN = '/^[a-z0-9-]{1,40}$/'`, `CODE_PATTERN = '/^VN-(PROM|WED)-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/'`
- `$fillable = ['target','loc','event','code','meta','redeemed_at']`, `$casts = ['meta'=>'array','created_at'=>'datetime','redeemed_at'=>'datetime']`
- Помощник `public static function prefixFor(string $target): string` → `PROM|WED`.

### 4.3 `App\Support\Assets::versioned(string $path): string`
`asset($path).'?v='.(is_file(public_path($path)) ? filemtime(public_path($path)) : '0')`. Guard-ът е нужен, защото warning от `filemtime()` става `ErrorException` в тестовете.

### 4.4 `GameController`
Константи: `DISCOUNT_PERCENT = 15`, `VALIDITY_HOURS = 72`, `MAX_BODY_BYTES = 4096`, `PAGE = ['prom' => [title, description, theme '#050807'], 'wedding' => [title, description, theme '#0b0b0f']]` с заглавия „Протокол: Излизане от Матрицата [Варна]“ и „Логически пъзел: Сватбената маса“.

`show(Request $request)`:
1. `$target` = `is_string` + `strtolower(trim())` + `in_array(..., TARGETS, true)` иначе `'prom'` (покрива и `?target[]=x`).
2. `$loc` = `is_string` ? `Str::slug(Str::lower($raw))` → `mb_substr(0,40)` → `trim('-')` : null; `''` → null. Резултатът винаги отговаря на `LOC_PATTERN` или е null.
3. Instagram: `$url = Settings::socialLinks()['instagram'] ?? 'https://www.instagram.com/taketwostudio1603'`; handle = първият сегмент от `parse_url($url, PHP_URL_PATH)`, `ltrim('@')`, fallback `taketwostudio1603`.
4. `$legalUrl` = `Route::has('legal.igra') && LegalPage::where('slug','igra-usloviya')->where('is_published',true)->exists() ? route('legal.igra') : null`.
5. `$config = ['target','loc','eventUrl'=>route('game.event'),'instagramHandle','instagramUrl','logoUrl'=>'/css/img/logo-tts-white.webp','studioName'=>Settings::siteName(),'discountPercent'=>15,'validityHours'=>72,'seasonYear'=>PromSeason::year(),'legalUrl']`.
6. `return response()->view('game.index', [...])->header('X-Robots-Tag', 'noindex, nofollow')`.

`event(Request $request)`:
```php
if (strlen($request->getContent()) > self::MAX_BODY_BYTES) return response()->json(['message'=>'Payload too large'], 413);
$input = $request->isJson() ? $request->all() : (json_decode($request->getContent(), true) ?: $request->all()); // sendBeacon text/plain fallback
$v = Validator::make($input, [
    'target' => ['required','string', Rule::in(GameEvent::TARGETS)],
    'loc'    => ['nullable','string','regex:'.GameEvent::LOC_PATTERN],
    'event'  => ['required','string', Rule::in(GameEvent::EVENTS)],
    'code'   => ['nullable','string','regex:'.GameEvent::CODE_PATTERN,'required_if:event,voucher,share','prohibited_unless:event,voucher,share'],
    'meta'   => ['nullable','array:lives_left,seconds,attempts,solved,method'],
    'meta.lives_left' => ['nullable','integer','between:0,4'],
    'meta.seconds'    => ['nullable','integer','between:0,86400'],
    'meta.attempts'   => ['nullable','integer','between:0,999'],
    'meta.solved'     => ['nullable','integer','between:0,4'],
    'meta.method'     => ['nullable','string', Rule::in(['share','download','preview'])],
]);
$v->after(fn ($v) => isset($input['code'], $input['target']) && !str_starts_with($input['code'], 'VN-'.GameEvent::prefixFor($input['target']).'-') ? $v->errors()->add('code', 'Prefix/target mismatch') : null);
if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);   // изрично 422, никога redirect
$data = $v->validated(); $meta = array_filter($data['meta'] ?? [], fn ($x) => $x !== null && $x !== '');
foreach (['lives_left','seconds','attempts','solved'] as $k) if (isset($meta[$k])) $meta[$k] = (int) $meta[$k];
$meta['device'] = self::deviceClass($request);   // UA се чете, не се пази
GameEvent::create(['target'=>$data['target'],'loc'=>$data['loc'] ?? null,'event'=>$data['event'],'code'=>$data['code'] ?? null,'meta'=>$meta]);
return response()->noContent();
```
`deviceClass()`: `Sec-CH-UA-Mobile` header (`?1` → mobile, `?0` → desktop), иначе `preg_match('/Mobi|Android|iPhone|iPad|iPod/i', UA)`; iPadOS се брои за desktop (известно ограничение). Изричният `Validator::make` е задължителен: `sendBeacon` праща `Accept: */*`, `expectsJson()` е false и `$request->validate()` би върнал 302.

### 4.5 Маршрути, CSRF, cookie-free група
`routes/web.php` (GET след ред 26 при страниците, POST при API блока):
```php
// QR игра (/igra?target=prom|wedding&loc=<стикер>) – noindex, без nav/sitemap, без сесия/бисквитки.
Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
])->group(function () {
    Route::get('/igra', [App\Http\Controllers\GameController::class, 'show'])->name('game.show');
    Route::post('/api/igra/event', [App\Http\Controllers\GameController::class, 'event'])->name('game.event')->middleware('throttle:120,1');
});
```
`bootstrap/app.php` (защита в дълбочина + документирана политика): `$middleware->validateCsrfTokens(except: ['api/igra/*']);` с коментар защо. `robots.txt` вече има `Disallow: /api/`; **не** добавяй `Disallow: /igra` (Google трябва да види noindex). Print-URL за стикерите: `https://taketwostudio1603.com/igra?target=prom&loc=mg` (https, без www, без завършващ `/`, `loc` малки латински букви ≤ 40).

### 4.6 Layout и view
`resources/views/layouts/game.blade.php` – собствен `<head>`:
```blade
<!DOCTYPE html><html lang="bg"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="dark">
<meta name="theme-color" content="@yield('theme_color', '#050807')">
<meta name="robots" content="noindex, nofollow">
<meta name="format-detection" content="telephone=no">
<title>@yield('title')</title>
<meta name="description" content="@yield('meta_description')">
<meta property="og:type" content="website"><meta property="og:url" content="@yield('og_url')">
<meta property="og:title" content="@yield('og_title')"><meta property="og:description" content="@yield('meta_description')">
<meta property="og:image" content="{{ asset('css/img/social-share-cover.jpg') }}"><meta property="og:locale" content="bg_BG">
<meta property="og:site_name" content="Take Two Studio 1603"><meta name="twitter:card" content="summary_large_image">
<link rel="preload" href="{{ asset('fonts/montserrat/montserrat-400-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/montserrat/montserrat-700-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('fonts/montserrat/montserrat.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Assets::versioned('css/igra.css') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('css/img/favicon_io/favicon-32x32.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('css/img/favicon_io/apple-touch-icon.png') }}">
</head>
<body class="igra" data-target="{{ $target }}" data-screen="intro">
@yield('content')
<script type="application/json" id="igra-config">{!! json_encode($config, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR) !!}</script>
<script src="{{ \App\Support\Assets::versioned('js/igra.js') }}" defer></script>
</body></html>
```
Правила: нищо от `layouts/app.blade.php` (без bootstrap/fontawesome/aos, без partials, без GA/Clarity), **без инлайн `<style>`** и без `csrf-token` meta (`PagesRenderTest`). `JSON_HEX_TAG` прави `</script>` в `loc` безвреден. `og:url` = `url('/igra?target='.$target)` (loc нарочно не влиза в споделяния URL).

`resources/views/game/index.blade.php` (`@extends('layouts.game')`): секции `title` (заглавие по target + ` | Take Two Studio 1603`), `meta_description`, `theme_color`, `og_title`, `og_url`; съдържание:
- `<noscript><p class="igra-noscript">Играта изисква включен JavaScript. Пиши ни в Instagram: @taketwostudio1603</p></noscript>`
- `<main class="igra-app" id="igra-app">` с header (`// 1603` mono „намигване“, без лого; бутон `#btn-mute` с inline SVG, `aria-pressed`).
- `#screen-intro`: prom → `<h1 class="igra-title glitch" data-text="ПРОТОКОЛ: ИЗЛИЗАНЕ ОТ МАТРИЦАТА [ВАРНА]">…</h1>`, lead `> 16 сигнала. 4 скрити връзки. 4 опита.`, бутон „Влез в системата“; wedding → `<h1 class="igra-title igra-title--terminal"><span class="type" data-text="ЛОГИЧЕСКИ ПЪЗЕЛ: СВАТБЕНАТА МАСА">…</span><span class="cursor"></span></h1>`, lead `> 6 гости. 6 места. 4 правила.`, бутон „Започни“.
- `#screen-game` (`hidden`): `@include($target === 'wedding' ? 'game.partials.seating' : 'game.partials.connections')`.
- `#screen-reveal` (`hidden`): boot ред `> ДЕКРИПТИРАНЕ… OK`, `<h2>Загадката е разгадана.</h2>`, `<p>Ти виждаш детайлите, които останалите пропускат. Точно така улавяме и твоите моменти.</p>`, `<img src="{{ $config['logoUrl'] }}" alt="{{ $config['studioName'] }}" class="rv-logo" decoding="async">` (**без width/height атрибути** – CSS оразмерява), карта с `.rv-badge` „15% OFF VOUCHER“, `<code id="rv-code">`, бутон „Копирай“, `<p id="rv-timer" role="timer">Валиден 72 часа</p>`, бутон „Генерирай Story Артефакт“, `<figure id="rv-preview" hidden>`, бутони „Сподели“/„Свали артефакта“ (hidden), `<ol>` 1. Свали артефакта. 2. Качи го на Instagram Story с таг @taketwostudio1603. 3. Изпрати ни кода на лично съобщение в рамките на 72 часа, за да запазим датата ти.; линк „Отвори @taketwostudio1603“ (`target=_blank rel=noopener`); `@if($config['legalUrl'])` линк „Условия на играта и ваучера“.
- `<p class="visually-hidden" id="igra-live" aria-live="polite">`; `<canvas id="story-canvas" width="1080" height="1920" hidden>` (или създаден в JS).

Partials: `connections.blade.php` = заглавие „Открий четирите връзки“, `#cx-lives` (4 × `.cx-life`), `#cx-grid` (JS рендира), `#cx-msg`, бутони „Размести“ / „Изчисти“ / „Провери връзката“ (`disabled`), `#cx-lose` панел (`> Връзката е прекъсната.` / „Опитите свършиха. Категориите са разкрити по-горе.“ / „Опитай отново“). `seating.blade.php` = заглавие „Настани гостите според правилата“, `<details open>` с 4 `<li class="st-rule" data-rule="r1..r4">`, `.st-table` с `.st-disc` („МАСА 01“) и 6 `<button class="st-seat" data-seat="1..6" aria-label="Място N: свободно">`, `#st-tray` (JS рендира 6 гости), `#st-msg`, бутони „Изчисти масата“ / „Потвърди местата“ (`disabled`).

### 4.7 Filament
`GameEventResource` (по образец `ActivityLogResource`): `$navigationGroup = 'Маркетинг'`, `$navigationIcon = 'heroicon-o-qr-code'`, `$navigationLabel = 'QR Игра'`, `$modelLabel = 'Събитие от играта'`; `canCreate/canEdit/canDelete → false`; страници само `index` (`ListGameEvents`, `getHeaderActions() = []`); badge в навигацията = брой `event=voucher` за последните 72 ч. Таблица (`defaultSort('created_at','desc')`, без bulk actions): `created_at ->dateTime('d.m.Y H:i', 'Europe/Sofia')`, `target` badge (Бал/Сватба), `loc` badge, `event` badge (Сканиране/Победа/Загуба/Ваучер/Споделяне), `code` mono `->copyable()->searchable()`, `redeemed_at` (Sofia), `meta` като `k: v` списък. Филтри: `SelectFilter target`, `SelectFilter event`, `SelectFilter loc` (distinct), `TernaryFilter redeemed`. Row action **„Маркирай като използван“** (`visible: event === 'voucher' && redeemed_at === null`, confirm, `update(['redeemed_at' => now()])`). Описание в ресурса: „Кодовете от играта НЕ работят в калкулаторите – проверявай ги тук: код + дата (72 ч) + Story с таг.“

**Статистика – отделна страница в „Маркетинг“ (решение на собственика от 2026-09-17: „сложи ги в Маркетинг частта“, не на Dashboard).** `app/Filament/Pages/GameStats.php` (`$navigationGroup = 'Маркетинг'`, label „QR Игра – статистика“, slug `game-stats`, sort 1, header action „Всички събития и кодове“ → ресурса) с два header widget-а, които живеят в `app/Filament/Resources/GameEventResource/Widgets/` (нарочно извън `app/Filament/Widgets`, за да не се откриват автоматично за Dashboard):
- `GameOverviewWidget` (`StatsOverviewWidget`): 4 карти – Сканирания (+ последните 7 дни, sparkline за 14 дни), Победи (% от сканиранията), Активни ваучери (72 ч, неизползвани), Използвани ваучери (% от издадените).
- `GameStatsWidget` (по образец `PromoCodeStatsWidget`, `$view = 'filament.widgets.game-stats'`): една заявка `SELECT target, loc, SUM(CASE WHEN event='scan' THEN 1 ELSE 0 END) scans, … wins, losses, vouchers, shares FROM game_events GROUP BY target, loc` (CASE WHEN → работи и в SQLite за тестове), сортиране по scans desc, конверсия `wins/scans` и `vouchers/scans` със zero guard; null `loc` → „(без локация)“. Blade: секция „QR Игра – статистика по локация“, таблица на target (Абитуриентски бал / Сватба) с колони Локация | Сканирания | Победи | Загуби | Ваучери | Споделяния | Конверсия, tfoot тотали, empty state, линк „Провери код от DM“.
Страницата (`resources/views/filament/pages/game-stats.blade.php`) показва и „Сканирани локации“ (всяка `loc` с поне едно сканиране, свързана със стикера ѝ или с бутон „Създай стикер“, който prefill-ва формата през `?target=&loc=`) и кратка инструкция „Как да валидираш код от Instagram DM“. **Без hardcoded линкове** (решение на собственика, 2026-09-18).

**QR стикери – ресурс в „Маркетинг“ (решение на собственика, 2026-09-18: „не искам hardcoded линкове, искам да мога да ги създавам аз“).** Таблица `game_stickers` (миграция `2026_09_18_000000_create_game_stickers_table.php`: `name`, `target`, `loc`, `notes`, `placed_at`, unique `[target, loc]`) + модел `App\Models\GameSticker` (`url` accessor през `GameController::url()`, scope `withEventCounts()` с subselect броячи от `game_events` по `target + loc`; без FK – събитията се пазят и след изтриване на стикер). `GameStickerResource` (`app/Filament/Resources/GameStickerResource.php`, sort 0): форма име / игра / `loc` (авто-slug от името при създаване, санитизиран при blur през `GameController::sanitizeLoc()`, regex + unique за играта) / поставен на / бележки; таблица с линк (copyable), сканирания, победи, ваучери; действия „QR код“ (модал), „Отвори“, редакция, изтриване. `CreateGameSticker::fillForm()` чете `?target=&loc=`.

**QR генератор в браузъра (без сървърна зависимост).** `public/vendor/qrcode/qrcode.min.js` (qrcode-generator 1.4.4, MIT, Kazuhiko Arase, само матрицата) + `public/js/admin/game-qr.js` (`window.GameQr`: `matrix`, `svg`, `png`, `panel` за Alpine). Регистрирани за целия панел през `FilamentAsset::register([...], package: 'taketwo')` в `AppServiceProvider::boot()` (не през `Panel::assets()`, защото Filament регистрира panel assets и в `register()`, и в `boot()` и ги зарежда два пъти). Partial `resources/views/filament/partials/game-qr-panel.blade.php` (ползван от модала `resources/views/filament/game-stickers/qr.blade.php`): стил „черен фон, бели модули“ (по избор на собственика), класически, „черен фон, златни модули“; център „?“ (кръг + въпросителна), лого на студиото (`logo-tts-white.webp` за тъмен фон / `logo-tts-black.png` за светъл) или без; квадратни/кръгли модули (finder pattern-ите остават квадратни); корекция на грешки H (30%), knockout в центъра ≤ ~7% от площта; сваляне PNG 1024/2048/4096 и SVG (лого вградено като data URI), копиране на линка. Проверено с OpenCV: всички стилове се декодират до точния URL (обърнатите – след инверсия, както правят камерите на iPhone/Android); в панела има предупреждение да се тестват с 2–3 телефона преди печат. Ресурсът със събитията е със label „QR Игра – събития“ (sort 2) в същата група.

### 4.8 Правна страница (опционално, ниска важност)
`GameLegalPageSeeder` с `LegalPage::firstOrCreate(['slug'=>'igra-usloviya'], [...])` (никога `updateOrCreate` – не презаписва админ редакции); съдържание: организатор; механика (QR → игра → код); 15% за пълно фото+видео заснемане; валидност 72 ч от генерирането по сървърния запис; един ваучер на човек/резервация; не се комбинира с други промоции; валидация само през DM в Instagram @taketwostudio1603 с тагната Story; не важи в онлайн калкулаторите; студиото може да откаже код с изтекъл срок; играта не събира лични данни (само анонимни събития). Route: `Route::get('/igra-usloviya', fn () => app(LegalPageController::class)->show('igra-usloviya'))->name('legal.igra')` (рендира се в стандартния layout през `legal.blade.php`). Продукция: `php artisan db:seed --class=GameLegalPageSeeder --force` веднъж през SSH или създай реда във Filament → Правни страници. **Не** го добавяй в `.cpanel.yml`.

### 4.9 Тестове (`tests/Feature/GameTest.php`, `RefreshDatabase`)
1. `test_game_page_renders_for_each_target` (provider prom/wedding): 200; `<meta name="robots" content="noindex, nofollow">`; header `X-Robots-Tag`; `<body … data-target="{t}"`; няма `class="navbar`, `cookieConsentBanner`, `bootstrap.min.css`, `fontawesome`, `aos.css`, `googletagmanager`, `name="csrf-token"`, `<style`; config JSON (`preg_match` по `id="igra-config"`) с `target`, `loc === 'mg'`, `eventUrl === url('/api/igra/event')`, `discountPercent === 15`, `validityHours === 72`, `instagramHandle === 'taketwostudio1603'`, `logoUrl` започва с `/`; `<img … logo-tts-white.webp` без `width=`/`height=`; `igra.css?v=` и `igra.js?v=`; съдържа `15%`, не съдържа `25%`.
2. `test_invalid_or_missing_target_defaults_to_prom` (`/igra`, `?target=hack`, `?target[]=x`).
3. `test_loc_is_sanitized` (`Morska Gradina!`→`morska-gradina`, `MG`→`mg`, `''`→null, 50 символа→40, `</script><script>` → няма `</script><script>` в config тага).
4. `test_event_is_stored_and_returns_204` (`postJson`, iPhone UA → `meta.device === 'mobile'`, `created_at` не null, няма колони ip/user_agent).
5. `test_event_accepts_text_plain_json_body` (`call('POST', …, content: json, CONTENT_TYPE text/plain)` → 204).
6. `test_event_rejects_invalid_payloads` (provider: target `hack`; event `click`; loc с интервал / 41 символа; code `VN-PROM-12`, `vn-prom-abcd`, `VN-PROM-ABC0`; code при `scan`; липсващ code при `voucher`; `VN-WED-…` с target prom; неизвестен meta ключ `email`; `lives_left` 9; meta като string) → 422 и за `postJson`, и за plain `post` (не 302); `assertDatabaseCount('game_events', 0)`.
7. `test_event_caps_payload_size` (5 KB body → 413).
8. `test_event_is_rate_limited_per_ip` (120 × 204, 121-ви → 429).
9. `test_event_endpoint_is_csrf_exempt_and_route_has_throttle` (`ReflectionProperty(ValidateCsrfToken::class,'neverVerify')` съдържа `api/igra/*`; middleware на `game.event` съдържа `throttle:120,1`).
10. `test_game_routes_set_no_cookies` (GET `/igra` и POST event → `assertHeaderMissing('Set-Cookie')`).
11. `test_game_page_is_not_in_sitemap_or_nav` (`$this->seed()`; `/sitemap-pages.xml` без `/igra`; `/` без `href="/igra`).
12. `test_public_igra_directory_must_not_exist` (`assertDirectoryDoesNotExist(public_path('igra'))` – иначе root `.htaccess` връща 403 за `/igra`).
13. `test_admin_can_browse_game_events_and_dashboard_shows_stats` (`User::factory()->create(['is_admin'=>true])`, редове scan/win/voucher за prom/mg; `/admin/game-events` вижда кода; `/admin` вижда „QR Игра“).
Плюс `PagesRenderTest::pages()` += `['/igra?target=prom'], ['/igra?target=wedding']`. Команди: `php artisan test --filter=GameTest`, после `php artisan test`; `vendor/bin/pint --dirty`.

## 5. Frontend – стъпки

### 5.1 `public/css/igra.css` (ред: reset+токени → layout → компоненти → Connections → Seating → Reveal → keyframes → media queries)
Токени:
```css
:root{--gold:#D4AF37;--font:'Montserrat',system-ui,sans-serif;--mono:ui-monospace,SFMono-Regular,Menlo,"Roboto Mono","Droid Sans Mono",monospace;--radius:8px;--tap:48px;--ease:cubic-bezier(.2,.8,.2,1)}
body[data-target="prom"]{--bg:#050807;--panel:#0b0f0c;--panel-2:#101712;--line:rgba(57,255,20,.22);--text:#e6ffe9;--dim:rgba(230,255,233,.62);--accent:#39ff14;--accent-2:#00e5ff;--ok:#39ff14;--bad:#ff3b3b;--band-1:#39ff14;--band-2:#00e5ff;--band-3:#9d5cff;--band-4:#ff3d81}
body[data-target="wedding"]{--bg:#0b0b0f;--panel:#151518;--panel-2:#1c1c22;--line:rgba(212,175,55,.32);--text:#f4f1ea;--dim:rgba(244,241,234,.62);--accent:#D4AF37;--accent-2:#f1e3b5;--ok:#7ddc9a;--bad:#ff6b6b}
```
Златото в prom темата се ползва **само** в `.rv-*` (бадж, разделителна линия).

Layout: `body{min-height:100vh;min-height:100dvh;background:var(--bg);color:var(--text);font:400 16px/1.5 var(--font);padding:env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);touch-action:manipulation;-webkit-tap-highlight-color:transparent;overflow-x:hidden}`; `.igra-app{max-width:520px;margin:0 auto;padding:12px 16px 32px;display:flex;flex-direction:column;gap:16px}`; бутоните за проверка в `.igra-actions{position:sticky;bottom:0;padding-bottom:env(safe-area-inset-bottom);background:linear-gradient(transparent,var(--bg) 30%)}` (никога `position:fixed`). На ≥768px: рамкирана „телефонна“ колона с `border:1px solid var(--line);border-radius:16px`.

Компоненти: `.igra-btn` (min-height 48px, uppercase, `--primary` = запълнен accent, `--ghost` = контур, `:active{transform:scale(.97)}`, `:disabled{opacity:.35;pointer-events:none}`); `.igra-msg` (`.is-error`/`.is-ok`, `msg-in` 220ms); `.igra-title{font:700 clamp(1.35rem,6vw,1.9rem)/1.15 var(--font);text-transform:uppercase}`; `:focus-visible{outline:2px solid var(--accent);outline-offset:3px}`; `.visually-hidden`; hover само под `@media (hover:hover)`.

Glitch заглавие (prom): `.glitch::before/::after{content:attr(data-text);position:absolute;inset:0;pointer-events:none}` с цветове `--accent-2`/`--accent` и keyframes `glitch-a` (3.4s) / `glitch-b` (2.9s) – `clip-path:inset(...)` + `translate(±3px)` само в ~10% от цикъла (пауза иначе); `.glitch.is-burst{animation-duration:.6s}` за еднократен интензивен burst. Терминално заглавие (wedding): `::before{content:"> "}`, `.cursor::after{content:"▍";animation:blink 1s steps(1,end) infinite}`.

Connections: `.cx-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:minmax(64px,auto);gap:6px}` (`minmax(0,1fr)` спира разширяването на колоните); `.cx-tile{min-height:64px;padding:6px 4px;font:600 clamp(.62rem,2.9vw,.85rem)/1.2 var(--font);text-align:center;display:flex;align-items:center;justify-content:center;overflow-wrap:anywhere;hyphens:auto;user-select:none}`; класове по дължина `.cx-tile--l` (≥26 знака) и `.cx-tile--xl` (≥34 знака) с по-малък clamp; `.is-selected` (accent контур, `translateY(-2px)`), `.is-wrong` (`cx-shake` 380ms: translateX 0,-6,6,-4,4,0), `.is-correct` (`cx-pop` 300ms); `.cx-band{grid-column:1/-1;background:var(--band);color:var(--bg)}` с `cx-band-in` (clip-path от центъра); `.cx-life` ромб, `.is-lost{transform:rotate(45deg) scale(.35);opacity:.25}`; `@media (max-width:339px){.cx-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}` (2 колони само под 340px). Сметка за 360px: плочка ≈ 77px; най-дългата дума „въображаем“ при ~10.4px ≈ 62px → влиза; най-дългата плочка (38 знака) → 4 реда ≈ 50px в 64px мин. височина.

Seating: геометрия само в CSS – `.st-table{--size:min(86vw,340px);--seat:64px;--r:calc(var(--size)*.40)}`, `.st-seat{position:absolute;left:50%;top:50%;transform:rotate(var(--a)) translate(var(--r)) rotate(calc(-1*var(--a)))}` с `--a` = -90°, -30°, 30°, 90°, 150°, 210° за столове 1–6 (стол 1 на 12 часа, по часовника); състояния `.is-target` (пунктир), `.is-over` (scale 1.12), `.is-occupied`, `.is-selected`, `.is-conflict` (shake + червено), `.is-lit` (зелено със `transition-delay:calc(var(--i)*90ms)`); `.st-disc.is-win{animation:st-pulse .9s}`. Гости `.st-guest` (grid 36px монограм + име + описание + `→ N`), **`touch-action:none` само на `.st-guest` и `.st-seat.is-occupied`**, `-webkit-touch-callout:none`; `.st-ghost{position:fixed;pointer-events:none;will-change:transform}`; правила `.st-rule::before{content:"[ ]"}`, `.is-pass::before{content:"[OK]";color:var(--ok)}`, `.is-fail::before{content:"[ X]";color:var(--bad)}`.

Reveal: `.rv>*{animation:rv-in .5s both;animation-delay:calc(var(--i)*140ms)}` (`--i` през `:nth-child`); `.rv-badge` (златен pill, тъмен текст); `.rv-code{font:700 clamp(1.3rem,7vw,1.9rem)/1 var(--mono);letter-spacing:.08em}`; `.rv-card.is-expired .rv-code{text-decoration:line-through}`; `.rv-logo{width:clamp(140px,42vw,220px);height:auto}`; `.rv-preview img{width:min(60vw,240px);aspect-ratio:9/16}`.

Motion/a11y: `@media (prefers-reduced-motion:reduce){*{animation-duration:.01ms!important;transition-duration:.01ms!important} .glitch::before,.glitch::after,.cursor::after{display:none}}`. Анимират се само `transform`, `opacity`, `clip-path`; никакъв `filter`/`backdrop-filter`/анимиран `box-shadow`; `will-change` само на `.st-ghost`.

### 5.2 `public/js/igra.js` – карта на модулите (един IIFE, `'use strict'`, ES2017 без `?.`/`??`; feature gate: `fetch`, `Promise`, `URLSearchParams`, иначе показва `#igra-unsupported`)
- **Помощници:** `$`, `qs`, `qsa`, `el`, `on`, `pad2`, `noop`, `REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches`.
- **`readConfig()`** – `JSON.parse(#igra-config)` върху defaults; `target !== 'wedding' → 'prom'`; `loc` ре-валидиран по `/^[a-z0-9-]{1,40}$/`. JS не чете `location.search`.
- **`STR`** – всички динамични низове: `wrong(n)` → `Грешна хипотеза. Остават N опита.` (`Остава 1 опит.` при 1), `oneOff: 'Една плочка разлика.'`, `repeated: 'Вече опита тази комбинация.'`, `solved(name)`, `livesLabel(n)`, `seatAll: 'Настани всички гости, преди да потвърдиш.'`, `conflict: 'Има конфликт на масата. Провери отбелязаните правила.'`, `seatsOk: 'Всички правила са спазени.'`, `moveHint: 'Избери място за госта.'`, `copied: 'Копирано!'`, `copyFail: 'Маркирай кода и го копирай ръчно.'`, `validFor(hms)` → `Валиден още HH:MM:SS`, `expired: 'Изтекъл'`, `rendering: '> Рендиране на артефакта…'`, `rendered: '> Артефактът е готов.'`, `renderFail`, `holdToSave: 'Задръж снимката, за да я запазиш.'`, `boot: '> ДЕКРИПТИРАНЕ… OK'`, `fileName(code)` → `taketwo-story-<code>.jpg`.
- **`DATA`** – 4 групи × 4 плочки (id = group*4+i) и 6 гости + 4 предикати (виж §6).
- **`Store`** – ключ `igra:v1:<target>`; `probe()` в try/catch, in-memory fallback; схема:
  `{v:1, target, loc, screen:'intro'|'game'|'lose'|'reveal', startedAt, muted:false, logged:{scan,win,voucher}, game: prom→{seed:uint32, lives:4, solved:[groupId], picked:[tileId], guesses:['3-7-9-12'], attempt} | wedding→{seats:[6×guestId|null], attempts}, voucher:{code, createdAt, expiresAt}}`. Редът на плочките се извежда от `seed` (mulberry32 + Fisher–Yates) – рефрешът не разбърква. Препоръчително: огледално копие само на `voucher` в `localStorage['igra:voucher:<target>']` (без лични данни; кодът оцелява при затворен таб) – константа `MIRROR_VOUCHER = true`.
- **`Events`** – `log(event, meta, code)`: `JSON.stringify({target, loc, event, code||null, meta||null})` → `fetch(eventUrl,{method:'POST',headers:{'Content-Type':'application/json'},body,keepalive:true,credentials:'omit'}).catch(noop)`; при липса на `fetch`/грешка → `navigator.sendBeacon(eventUrl, new Blob([body],{type:'text/plain'}))`; всичко в try/catch, никога не се `await`-ва. `once(event,…)` дедупира през `Store.logged`. Извиквания: `once('scan')` при boot; `once('win',{lives_left,seconds,attempts})`; `log('lose',{lives_left:0,seconds,attempts,solved})`; `once('voucher',null,code)`; `log('share',{method:'share'|'download'|'preview'},code)`.
- **`Sound`** – `AudioContext` се създава лениво в capture-listener на първия `touchend`/`pointerup`/`keydown`; всяко `play` прави `if(ctx.state!=='running') ctx.resume()`; `visibilitychange`/`pageshow` → resume; master `GainNode` (mute = gain 0, персистира в Store). Тембри: prom `{lead:'square',pad:'sawtooth',chord:'square'}`, wedding `{lead:'sine',pad:'triangle',chord:'triangle'}`. Обвивка: `gain 0.0001 → peak за 8ms → exponentialRamp до 0.0001 за dur`. Звуци: `blip` (880→1320 Hz, 90ms, peak .16), `unblip` (660→520, 80ms), `solve` (C5 523 Hz + G5 784 Hz през 110ms), `error` (бас 110→55 Hz, 420ms, peak .32, lowpass 420 Hz; +square 82→41 при prom), `victory` (C5-E5-G5-C6 арпеж през 90ms, 1.7s). Всичко в try/catch – звукът никога не чупи логиката.
- **`Connections`** – `init()` (state от Store или `fresh()`), `order()` (seed → shuffle → без решените групи), `render()` (пълен ре-рендер: ленти на решените групи + плочки, `--i` stagger), `onTile(id)` (toggle, cap 4, `aria-pressed`, blip/unblip), `check()`: ключ = сортирани id → ако е в `guesses` → `STR.repeated` без загуба на живот; ако 4/4 → `.is-correct`, `Sound.solve()`, `solved.push`, ре-рендер след 280ms, `STR.solved(name)`, при 4 групи → `win()`; иначе `lives--`, `.is-wrong` (shake), `Sound.error()`, съобщение `STR.wrong(lives)` (+ `STR.oneOff` при 3/4), при 0 → `lose()`: `Events.log('lose')`, разкриване на останалите групи като `.is-revealed`, панел „Опитай отново“ → `retry()` (нов seed, 4 живота). `shuffle()` ре-сийдва и запазва; `clear()` чисти избора. Съобщенията отиват и в `#igra-live`.
- **`Seating`** – `adj(a,b) = (a+1)%6===b || (b+1)%6===a`, `opp(a,b) = (a+3)%6===b` (0-базирани индекси); `seats[6]`; `select(id)`, `place(id, idx)` (swap ако е заето, освобождава старото място), `unseat(id)`, `onSeatTap`, `onTrayTap`; `evaluate()` → `[{id:'r1',pass}, …]`; `check()`: ако има свободен стол → `STR.seatAll`; иначе маркира правилата `is-pass`/`is-fail`, при провал – `.is-conflict` на замесените столове + `Sound.error()`; при успех → `celebrate()`: `Sound.victory()`, столове 1→6 светят през 90ms, правилата `[OK]` през 120ms, `st-pulse`, след 1400ms (300 при reduced motion) → `App.win({attempts})`. Drag (само ако `window.PointerEvent`): `pointerdown` записва старт; `pointermove` > 8px → `setPointerCapture`, `.st-ghost` клон (translate3d); `elementFromPoint` → `.is-over` на стол / `.is-dropzone` на подноса; `pointerup` → `place`/`unseat`/snap back; `pointercancel` → чисти; `suppressClick` 300ms след drag. Tap моделът остава основен и е напълно достатъчен.
- **`Voucher`** – `ALPHABET='23456789ABCDEFGHJKLMNPQRSTUVWXYZ'` (32 знака → `byte & 31` без bias); `randomChars(n)` с `crypto.getRandomValues` (fallback `Math.random`); `dateChar(d) = ALPHABET[Math.floor(dayOfYearUTC/3) % 32]` (3-дневен период ≈ 72-часовия прозорец); `getOrCreate()` → `{code: prefix + dateChar + randomChars(3), createdAt, expiresAt: now + 72h}` записан еднократно, после `Events.once('voucher', null, code)`. Регекс: `/^VN-(PROM|WED)-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/`.
- **`Reveal`** – `show()`: код в DOM, typewriter на boot реда, prom → `rv-glitch-once`, `tick()` всяка секунда от `expiresAt` (рефрешът продължава отброяването), при ≤0 → `Изтекъл`, `.is-expired`, генераторът се изключва; `copy()` → `navigator.clipboard.writeText` само при `isSecureContext` и с `.catch`, иначе `textarea + execCommand('copy')` fallback (за разлика от `promo-popup.blade.php:160-176`, който няма feature check).
- **`Story`** – `prepare(code)` (стартира при показване на reveal): `fontsReady()` (`document.fonts.load` с **кирилски примерни низове** за 700/600/500/400 – Montserrat е разделен по `unicode-range` и без кирилски текст се тегли само латинският файл; race с 1.8s timeout) + `loadImage(logoUrl, 2000ms)` → `draw()` → `toBlob('image/jpeg', .92)` → `blob`, `File`, `URL.createObjectURL`. `generate()` показва превюто и решава бутоните: `canShare = navigator.canShare && navigator.canShare({files:[file]})`; `inApp = /Instagram|FBAN|FBAV|FB_IAB|Viber|TikTok|Line\//i.test(UA)`; `isIOS`; ред: share → (`a[download]` само ако не е iOS и не е in-app) → превю + `STR.holdToSave`. `share()` е синхронен в click handler-а (`navigator.share({files,title,text})`, `AbortError` се игнорира). Fallback при `toBlob` грешка → `toDataURL`; при липса на лого → текстов wordmark „TAKE TWO STUDIO 1603“. Canvas е фиксирано 1080×1920 (без devicePixelRatio).
- **`App`** – `go(screen)` (hidden toggle, `body[data-screen]`, фокус на заглавието, scrollTo 0), `start()`, `win(meta)` (`Events.once('win')`, `Sound.victory()` при prom, `Reveal.show()`), `boot()`: bind mute, `Events.once('scan')`, `Game.init()`, възстановяване от Store (`reveal` с ваучер → направо победен екран; `game`/`lose` → игра; иначе intro с glitch burst / typewriter).

### 5.3 Story canvas – точна последователност (`W=1080, H=1920`, safe zone y∈[250,1670], поле 80 → макс. ширина текст 920; `P = target==='prom'`)
1. Фон: вертикален градиент `P ? ['#050807','#0b1a12','#050807'] : ['#0b0b0f','#1a1520','#0b0b0f']`.
2. Glow: радиален градиент в (540,720), r 720, accent@.18 → 0 (accent = `#39ff14` / `#D4AF37`, secondary = `#00e5ff` / `#f1e3b5`).
3. Текстура: prom → сканлинии на 6px (`rgba(255,255,255,.028)`) + 300 детерминистични точки (mulberry32 от char кодовете на кода); wedding → dot grid стъпка 48 (`rgba(212,175,55,.10)`) + hairline рамка `strokeRect(60,60,960,1800)` + 4 ъглови чертички.
4. Горен mono надпис y=300: prom `// PROTOCOL: VARNA · 1603`, wedding `// СВАТБЕНАТА МАСА · 1603` (`500 30px`, 55% бяло).
5. Заглавие: `fitFont('ДЕКРИПТИРАХ', 920, 118px, 700 Montserrat)` на y=560 и `КОДА ЗА ВАРНА` на y=700; prom – три слоя (secondary x−6, accent x+6, бяло) + един хоризонтален „срез“ с clip; wedding – градиент шампанско→злато.
6. Разделител `fillRect(440,790,200,2)`.
7. Бадж `roundRect(260,860,560,110,55)` (ръчен path, не `ctx.roundRect`) + `15% OFF VOUCHER` (`700 44px`, разредено през `measureText` по символ).
8. Подред y=1040 `15% отстъпка за фото и видео заснемане` (`400 34px`, 75% бяло, `wrapText`).
9. Кутия за кода `strokeRect(160,1090,760,150)` + кодът `fitFont(code, 700, 84px, mono)` y=1195, бяло.
10. `ВАЛИДЕН 72 ЧАСА` y=1330 (`500 32px`, accent).
11. CTA y=1440 `Тагни @taketwostudio1603 за валидация` (`600 36px`, бяло, wrap 920/46).
12. Лого: `w=300`, `h` по аспект (≈148), `drawImage` центрирано на y=1500; ако липсва → текст `TAKE TWO STUDIO 1603` (`700 40px`) y=1580.
13. Футър mono y=1655 `taketwostudio1603.com` (26px, 40% бяло), само ако логото свършва ≤1620.
Помощници: `lin()`, `rad()`, `roundRect()`, `fitFont()` (намалява по 4px до ≤ maxW, мин 40px), `drawSpaced()`, `wrapText()`. Бюджет: < 400ms на слаб Android; JPEG < 1 MB.

### 5.4 Хореография (наполовина/нула при reduced motion)
Intro prom: glitch burst 600ms → lead typewriter 18ms/знак → бутон fade. Intro wedding: заглавието се „пише“ 28ms/знак с курсор. Избор на плочка: 150ms + blip. Грешка: shake 380ms, един „живот“ се свива 300ms, бас импулс. Вярна група: pop 300ms → ре-рендер на 280ms → лента clip-path 380ms → останалите плочки stagger 25ms. Победа prom: последна лента → 650ms → reveal + акорд. Seating успех: акорд, столове 1→6 през 90ms, правила през 120ms, пулс на диска, 1400ms → reveal. Reveal: boot ред → заглавие (prom glitch once) → текст → лого + златна линия → карта → story блок → стъпки → IG линк, през 140ms.

### 5.5 Бюджети и качество
JS ≤ ~30 KB raw (LiteSpeed gzip-ва), CSS ≤ ~16 KB; нула външни заявки; общо ≈ 45 KB без шрифтове. Само `<button>` за плочки/гости/столове; `aria-pressed`, `aria-live`, фокус върху заглавието на всеки екран; контраст ≥ 7:1 за основния текст; цветовата обратна връзка винаги е дублирана с текст/символ (`[OK]`/`[ X]`, брой опити).

## 6. Игрово съдържание

**Игра А – категории (4×4):**
1. „NPC Бал Пози“: Гледане на въображаем часовник · Оправяне копчето на сакото · Поглед в безкрая · Хванат за ревера
2. „Кошмарите на 24 май“: Счупен ток на паветата · Разтекъл се грим от жега · Дъжд на фотосесията · DJ пуска Бяла роза в 21:00
3. „Персонажи от випуска“: Крипто батка с тясно сако · Девойка с 3 смени на тоалета · Пич с кецове под костюма · Момчето наел кола за 1000 лв
4. „Шофьорски фолклор“: Надуване на клаксон на Червения площад · Навеждане през шибидаха · Броене от 1 до 12 на светофара · Пътна полиция на изхода на Морската
Най-дълга плочка: 38 знака; най-дълга дума: „въображаем“ (10). Ако QA на 320–360px покаже нечетимост, собственикът може да съкрати 2–3 текста (напр. „Клаксон на Червения площад“).

**Игра Б – гости (монограм в кръг вместо емоджи/иконен шрифт; описанията са редактируеми defaults):** Свекървата (СВ, „Държи на протокола“) · Майката на булката (МБ, „Държи на своето“) · Купонджията Иван (ИВ, „Ще танцува само до Елена“) · Чичото с политиката (ЧП, „Има мнение за всичко“) · DJ-ят (DJ, „Иска пряк път до пулта“) · Шаферката Елена (ЕЛ, „Пази булката и Иван“).

**Правила (текст в `<details>`):** 1. Свекървата и Майката на булката не трябва да са една до друга, нито точно една срещу друга. 2. Купонджията Иван трябва да е до Шаферката Елена. 3. DJ-ят трябва да е на място 1. 4. Чичото с политиката не трябва да е до Свекървата.

**Валидни решения (проверени с пълно изброяване, столове 1–6 по часовника):** `{DJ:1, Св:2, Ив:3, Ел:4, Чи:5, Мб:6}`, `{DJ:1, Св:2, Ел:3, Ив:4, Чи:5, Мб:6}`, `{DJ:1, Мб:2, Чи:3, Ив:4, Ел:5, Св:6}`, `{DJ:1, Мб:2, Чи:3, Ел:4, Ив:5, Св:6}` – точно 4. Негативни фиксчъри за QA: Св=2/Мб=3 (р.1 съседни), Св=2/Мб=5 (р.1 срещу), Ив=3/Ел=5 (р.2), DJ=2 (р.3), Чи=3/Св=2 (р.4).

## 7. Рискове и митигации

| Риск | Тежест | Митигация | Тест |
|---|---|---|---|
| iOS: AudioContext suspended/interrupted → без звук | Висока | Ленив контекст в capture listener на първия жест; `resume()` преди всяко пускане и на `visibilitychange`/`pageshow`; try/catch навсякъде; документирай, че iOS уважава безшумния бутон | Първи тап → blip; след обаждане/Siri → следващият тап свири |
| `navigator.share` губи user activation след async `toBlob` → `NotAllowedError` на iOS | Критична | Пре-рендер при reveal; две стъпки (Генерирай → Сподели); `share()` синхронен в click; `busy` флаг срещу двоен тап | iPhone: един тап → share sheet; двоен тап → един sheet |
| `a[download]` безполезен на iOS (Files/нов таб) и блокиран в in-app браузъри | Висока | Ред share → download (не iOS, не in-app) → превю + „Задръж снимката…“ (screenshot винаги работи) | Матрица по устройства; Instagram in-app → превю |
| Canvas „tainted“ при разминаване на host | Висока | `logoUrl` root-relative; без `crossOrigin`; fallback wordmark; `toDataURL` fallback | Dev на `127.0.0.1` с `APP_URL=localhost` → артефакт с лого |
| Кирилицата на canvas пада на системен шрифт | Висока | `document.fonts.load(spec, <кирилски текст>)` за всички тегла + timeout 1.8s; preload 400/700 cyrillic | Slow 3G → Story с Montserrat кирилица |
| Дълги български текстове в 4×4 на 320–360px | Висока | `minmax(0,1fr)`, `clamp()` стълбица, `--l/--xl` класове, `overflow-wrap:anywhere`, редове `minmax(64px,auto)`, 2 колони < 340px | Sweep на 320/360/375/390/412/430; `scrollWidth === innerWidth` |
| Цял клас зад един NAT IP → 429 от Laravel/LiteSpeed | Висока | `throttle:120,1`; ≤4 POST-а на сесия; scan дедупиран; fire-and-forget (429 не влияе на играта); страницата ≤ 8 заявки | PHPUnit 429; burst тест от hotspot с 5 устройства; cPanel raw logs |
| Подправени/дублирани кодове (генерация в клиента) | Средна | Regex + префикс↔target на сървъра; справка в админа (ред `voucher`, ≤72 ч, `redeemed_at` null, тагната Story); загубата е ограничена до 15% | curl с грешен код → 422; Filament търсене по код |
| `sessionStorage` недостъпен (private mode, WebView) | Висока | probe + in-memory fallback; всички достъпи в try/catch; JSON.parse guard | Safari „Block All Cookies“ → играе се |
| Кодът се губи при затворен таб | Средна | Огледално копие на `voucher` в `localStorage` (без лични данни); текст „Свали артефакта сега“ | Победа → затвори таба → отвори QR URL → същият код |
| Drag срещу скрол на страницата; `pointercancel` губи drop | Висока | `touch-action:none` само на гостите; `setPointerCapture`; `pointercancel` → snap back; tap моделът е основен | Drag над правилата → без скрол; drag извън екрана → връщане |
| Двоен тап зумва при бързо кликане | Средна | `touch-action:manipulation` на body/бутони; без `maximum-scale` (WCAG 1.4.4) | Двоен тап на плочки → без зум; pinch работи |
| 422 става 302 при не-JSON `Accept` | Средна | Изричен `Validator::make` + `response()->json(…,422)` | Plain `post()` → 422 |
| Страницата попада в Google/sitemap | Средна | meta + `X-Robots-Tag` noindex; sitemap и nav са хардкоднати списъци; без `Disallow: /igra` в robots | Тест 11 |
| Създаване на `public/igra/` (напр. статичен прототип) → 403 за `/igra` | Критична | Тест `assertDirectoryDoesNotExist(public_path('igra'))`; коментар в layout-а | Тест 12 + production curl |
| Filament показва UTC | Ниска | `->dateTime('d.m.Y H:i', 'Europe/Sofia')` на всички дати; не пипай `app.timezone` | Ред → час в София |
| Сесийни бисквитки на „страница без бисквитки“ | Ниска | `withoutMiddleware([StartSession, ShareErrorsFromSession, ValidateCsrfToken])`; Blade без `session()/old()/$errors/csrf_field()` | Тест 10 `assertHeaderMissing('Set-Cookie')` |
| Стар CSS/JS след деплой (кеш 1 месец) | Средна | `Assets::versioned()` (`?v=filemtime`, mtime се сменя при git checkout) | `curl … \| grep 'igra.css?v='` след деплой |
| Копи с „25%“ или обещание за онлайн използване | Висока | `discountPercent` от config; тест за `15%`/без `25%`; инструкциите казват само DM; описание в админ ресурса | Тест 1 + copy audit |
| Правна страница 404 в продукция | Ниска | Линкът се рендира само ако редът е публикуван; seeder с `firstOrCreate` пуснат ръчно | Тест + curl `/igra-usloviya` |

## 8. Верификация

**Автоматично:** `php artisan test --filter=GameTest` → `php artisan test` (включително новите редове в `PagesRenderTest`); `vendor/bin/pint --dirty`.

**curl (локално `http://localhost:8000`, продукция `https://taketwostudio1603.com`):**
```bash
curl -sI 'https://taketwostudio1603.com/igra?target=prom&loc=mg'            # 200, X-Robots-Tag: noindex, nofollow, без Set-Cookie, без Location
curl -sI 'https://taketwostudio1603.com/igra/?target=prom&loc=mg'           # 301 → /igra?target=prom&loc=mg (query запазен)
curl -s  'https://taketwostudio1603.com/igra?target=wedding' | grep -cE 'navbar|bootstrap|fontawesome|aos\.css|gtag|csrf-token|<style'   # 0
curl -s -o /dev/null -w '%{http_code}\n' -X POST -H 'Content-Type: application/json' \
  -d '{"target":"prom","loc":"mg","event":"voucher","code":"VN-PROM-K7M3","meta":{"lives_left":2}}' https://taketwostudio1603.com/api/igra/event   # 204
curl -s -X POST -H 'Content-Type: application/json' -d '{"target":"prom","event":"scan","code":"VN-PROM-K7M3"}' .../api/igra/event   # 422 (code при scan)
curl -s -X POST -H 'Content-Type: application/json' -d '{"target":"wedding","event":"voucher","code":"VN-PROM-K7M3"}' .../api/igra/event  # 422 (префикс↔target)
for i in $(seq 1 121); do curl -s -o /dev/null -w '%{http_code} ' -X POST -H 'Content-Type: application/json' -d '{"target":"prom","event":"scan"}' http://localhost:8000/api/igra/event; done   # 120×204, после 429
```

**Матрица по устройства (ръчно):** iPhone Safari (текуща и предишна iOS) · iPhone SE 375×667 · Pixel Chrome · Samsung Galaxy A (Samsung Internet + Chrome, 360px) · слаб Android 2–3 GB · Instagram in-app (iOS + Android) · Viber in-app · iPad Safari · Desktop Chrome/Safari/Firefox (клавиатура) · Safari „Block All Cookies“ · Reduce Motion + VoiceOver/TalkBack · QR скенери (iOS Camera, Google Lens, Samsung Camera, Viber). За всяко: първи тап → звук; refresh на всеки екран → същото състояние; Story → кой клон сработи (share sheet / download / превю) и дали снимката стига до Instagram Story; плочките четими, без хоризонтален скрол; 4 валидни решения печелят, 5 негативни светят правилното правило; повторена грешна комбинация не отнема живот; загубата не дава ваучер.

**Filament:** Маркетинг → QR Игра: редовете в софийско време, търсене по точен код, „Маркирай като използван“ сетва `redeemed_at`; Dashboard widget показва сканирания/победи/ваучери/конверсия по loc; след реален скан на iOS и Android – редове `scan → win → voucher` с `device: mobile` и същия код след refresh.

**Release checklist (go/no-go):** тестове зелени · production curl-овете горе · `public/igra/` не съществува на сървъра · Lighthouse mobile ≥ 90 perf / ≥ 95 a11y · burst тест от hotspot без 429/403 на страницата · copy audit (без „25%“, без „калкулатор“) · QR артуърк: всеки URL сверен с каноничния шаблон и всеки `loc` сканиран веднъж, за да се появи в widget-а · rollback = revert на комита + `php artisan migrate:rollback --step=1`.

## 9. Ред на изпълнение

1. **Backend скелет (M):** миграция → `GameEvent` → `Assets` → `GameController` → маршрути + cookie-free група + `bootstrap/app.php` → `GameTest` за валидация/throttle/CSRF (TDD).
2. **Layout + view + partials + празни `igra.css`/`igra.js` (S):** `PagesRenderTest` редове; тестове 1–3, 10–12 зелени.
3. **CSS основа (M):** токени, layout (dvh, sticky actions, safe-area, touch-action), бутони, intro заглавия (glitch/terminal), reduced motion; визуална проверка на 320/360/390/430.
4. **JS ядро (S):** helpers, `readConfig`, `STR`, `DATA`, `Store`, `Events`, `App.go/boot`, scan дедупиране.
5. **Игра А – Connections (M):** engine + grid/tiles/bands/lives/lose; тест на всички 16 текста на 360px; refresh и retry.
6. **Игра Б – Seating (M):** tap модел → геометрия → правила → Pointer Events drag → верижна реакция; 4 положителни + 5 негативни фиксчъра.
7. **Sound (S):** ленив контекст, unlock на iOS, mute персистенция, обвивки.
8. **Voucher + Reveal (S):** генерация, 72-часов countdown от `expiresAt`, copy с fallback, expired състояние, `voucher` събитие, localStorage огледало.
9. **Story canvas + share (M):** fonts probe → лого → 13-стъпков draw → blob → превю → share/download/hold-to-save; тест на iOS Safari, Android Chrome, Instagram in-app.
10. **Filament (S):** `GameEventResource` (Sofia tz, търсене по код, „Маркирай като използван“) + `GameStatsWidget`; тест 13.
11. **Опционално правна страница (S):** seeder + route + условен линк; публикуване през Filament/SSH.
12. **Field QA (M):** матрица по устройства, burst тест, Lighthouse, copy audit, QR URL проверка; деплой през cPanel „Deploy HEAD Commit“ (`.cpanel.yml` пуска `migrate --force` и `optimize`; нищо друго не е нужно).

Размери: S ≈ 1–2 ч, M ≈ 3–5 ч. Общо ≈ 3–4 работни дни с QA.

## 9а. Промени от 2026-09-18 (искания на собственика след първата версия)

- **Кодът работи директно в сайта.** `App\Support\GameVoucher`: редът `voucher` в `game_events` е самият ваучер (72 ч, до `redeemed_at`). `OrderController::validatePromoCode()` приема кодове `VN-…`, когато няма `PromoCode` с такъв код; `submitOrder()` записва отстъпката и маркира ваучера като използван (еднократно). Победният екран има бутон „Използвай кода в сайта“ → `/proms?promo=CODE#calculator` или `/weddings?...` (`config.useUrl`); преди навигация JS гарантира, че `voucher` събитието е стигнало до сървъра (`Voucher.sync`, повторен опит, 2.5 с таймаут). Сървърът пази ваучера уникален по код (повторни beacon-и не създават дубликати). Ново събитие `use`.
- **Двустепенна отстъпка, редактируема в админа.** Настройки (SiteSetting) `game_discount_base_{prom|wedding}` (по подразбиране 5%) и `game_discount_shared_{prom|wedding}` (15%) – форма „Настройки на играта“ на страницата „QR Игра – статистика“ (`GameStats::saveSettings`, валидация shared ≥ base, 0–90). Двата процента се замразяват в ваучера при издаване (`meta.percent`, `meta.percent_shared`); събитие `share` с method `share|download|confirm` вдига `meta.percent` до shared (`GameVoucher::boost`, `meta.boosted_at`). В играта: бадж и текст показват текущия процент, ред „Сподели Story артефакта и отстъпката става X%“, бутон „Качих го в Instagram Story ✓“ за in-app браузъри (честна дума – студиото проверява тага при съмнение). Колона „Отстъпка“ в „QR Игра – събития“.
- **Story артефактът не показва кода** – само маскиран `VN-PROM-••••`; баджът рекламира увеличения процент; името на файла е `taketwo-story-varna.jpg` (без кода).
- **Поправен стар бъг: двойна отстъпка.** Калкулаторите (всички 4 JS файла) прилагат промо кода в браузъра и пращат вече намалена `final_price`; сървърът я намаляваше втори път. Сега `submitOrder` само записва `discount_amount` (`GameVoucher::discountFromDiscountedPrice`) и не изважда повторно – важи и за обикновените `PromoCode`. Тест: `tests/Feature/GameVoucherRedemptionTest.php`.

## 10. Остатъчни рискове и бележки за собственика

- **Кодовете са подправими по дизайн** (генерират се в JS). Реалната защита е ръчна: код в админа като ред `voucher` от последните 72 ч, без `redeemed_at`, плюс тагната Story и преценка на студиото. Максималната загуба е 15%.
- **Кодовете не работят в калкулаторите на сайта** и в `/api/validate-promo-code`; отстъпката се прилага ръчно при офертата. Ако по-късно искаш онлайн валидация, пътят е сървърно създаване на `PromoCode` ред (беше предложено и отказано на 2026-09-17).
- **Без cookie банер на страницата**, защото няма бисквитки, аналитика и лични данни; записват се само анонимни събития (тип игра, стикер, събитие, код, час, клас устройство).
- **Дублирани кодове** са възможни (32 768 комбинации на 3 дни на target) и безвредни при ръчна валидация по код + дата + локация.
- **Статус на изпълнението (2026-09-17):** backend, frontend, Filament (страница „QR Игра – статистика“ + ресурс „QR Игра – събития“ в „Маркетинг“) и тестове (`GameTest`, 35 случая; целият набор 124 теста) са имплементирани; end-to-end сценарий в headless Chrome минава за двете игри (логика, ваучер, Story JPEG 1080×1920). Огледалното копие на ваучера в `localStorage` е включено (`Store.mirrorVoucher`). Не е направено: правната страница `igra-usloviya` (опционална) и ръчната проверка на реални устройства по матрицата в секция 8.
- **Отворено за потвърждение (не блокира старта):** (а) съкращаване на 2–3 най-дълги плочки, ако QA на 360px покаже нечетимост; (б) дали правната страница `igra-usloviya` да влезе в първата версия.

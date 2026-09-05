<!--
ВЪТРЕШЕН ДОКУМЕНТ — само за екипа на Take Two Studio 1603.
Не публикувай, не споделяй линк. Съдържа детайли за хостинга, неотстранени уязвимости и бизнес приоритети.

РЕПОТО ТРЯБВА ДА Е PRIVATE. Към 2026-09-05 GitHub репото PresianGrigorovDev/TakeTwoStudio е публично и този файл
вече беше push-нат в него (branch laratake, commit 7b5754d). Стъпки на собственика:
  1. GitHub → Settings → General → Danger Zone → Change visibility → Make private.
  2. cPanel Git с private репо работи само през SSH: cPanel → Git Version Control → копирай публичния SSH ключ
     (или генерирай от cPanel → SSH Access) → GitHub → Settings → Deploy keys → Add (read-only).
     На сървъра: cd ~/public_html && git remote set-url origin git@github.com:PresianGrigorovDev/TakeTwoStudio.git
  3. Едва тогава push на нови commit-и.

Папка docs/ НЕ трябва да е достъпна през уеб: днес root .htaccess на сървъра пренаписва всичко към public/ (→ 404),
а новият root .htaccess от Част C.1 я блокира изрично (403). След деплой провери:
  curl -sI https://taketwostudio1603.com/docs/SEO-PLAN.md   # трябва 403/404

Източник: анализ от 2026-09-05 (Claude Code, plan mode). Обновявай този файл при промяна на плана.
-->

# План: `/public/` проблемът + пълна SEO/GEO оптимизация на taketwostudio1603.com

> Документът е план за действие (анализ от 2026-09-05); нищо от него не е приложено автоматично. Всяка точка по-долу е написана така, че да може да се изпълни по-късно (файл, конкретна промяна, команда за проверка).
> Дата на анализа: 2026-09-05. Branch: `laratake`. Всички твърдения за живия сайт са проверени с `curl` същия ден.

---

## Контекст

Сайтът е Laravel 12 + Filament (фото/видео студио във Варна) на cPanel shared хостинг с **LiteSpeed**, деплой чрез **cPanel Git Version Control**. Собственикът докладва, че URL-ите на услугите са станали `domain.com/public/weddings` вместо `domain.com/weddings` (както беше при стария статичен сайт). Отделно иска план за максимална видимост в Google, ChatGPT и Gemini за пазара **Варна + регион, само на български**, с приоритет: 1) абитуриентски балове, 2) сватби (фото + видео), 3) B2B (рекламна, автомобилна, архитектурна, събитийна).

Налични инструменти при собственика: Google Business Profile, Google Search Console, Bing Webmaster Tools, YouTube канал. Капацитет за съдържание: AI чернови + редакция от собственика, 2–4 материала месечно.

---

## Част A — Диагноза: защо URL-ите имат `/public/`

### A.1 Какво връща живият сайт днес

| Заявка | Резултат |
|---|---|
| `https://taketwostudio1603.com/weddings` | **200**, всички 33 линка в HTML са чисти |
| `https://taketwostudio1603.com/public/weddings` | **200** (дублирана страница, без redirect); **32 от 33 линка** в HTML са `/public/...` |
| `http://taketwostudio1603.com/weddings` | 301 → `https://taketwostudio1603.com/public/weddings` |
| `https://www.taketwostudio1603.com/weddings` | 301 → `https://taketwostudio1603.com/public/weddings` |
| `https://taketwostudio1603.com/weddings/` (наклонена черта) | 301 → `https://taketwostudio1603.com/public/weddings` |
| `https://www.taketwostudio1603.com/` | 301 → `https://taketwostudio1603.com/public/` |
| `https://taketwostudio1603.com/composer.json` | 404 (т.е. root правило пренаписва всичко към `public/`) |
| `https://taketwostudio1603.com/.env` | 403 |
| `<link rel="canonical">` и `og:url` на двете версии | чисти (`/weddings`) — само те |

Извод: **всеки посетител, който влезе през `http://`, `www.` или с наклонена черта накрая, бива пренасочен към `/public/...`** и оттам нататък цялата навигация е с `/public/`. Това е причината собственикът да „вижда“ `/public/` в адресната лента.

### A.2 Механизмът (стъпка по стъпка)

1. **Document root = root на Laravel проекта.** cPanel Git деплойва репото директно в `/home/mbgsqksf/public_html/` (виж [_legacy/.cpanel.yml](../_legacy/.cpanel.yml)). Папката `public/` на Laravel е *под* webroot-а, вместо да *е* webroot.
2. **Root `.htaccess` съществува само на сървъра.** В репото няма root `.htaccess` (старият е преименуван в commit `b12cb1c` и е в [_legacy/.htaccess_old](../_legacy/.htaccess_old)). `.gitignore` не го игнорира → файлът е създаден ръчно на сървъра и е untracked. Поведението (`/composer.json` → 404, `/index.php` → начална страница) показва правило от типа `RewriteRule ^(.*)$ public/$1 [L]`.
3. **Redirect правилата живеят в [public/.htaccess](../public/.htaccess#L8-L27) и използват `%{REQUEST_URI}`.** След per-directory rewrite (`RewriteRule ^(.*)$ public/$1 [L]`) mod_rewrite прави вътрешен redirect и на следващия pass `%{REQUEST_URI}` вече е `/public/weddings` — това е класическият бъг „Laravel на shared hosting пренасочва към /public/ при trailing slash“ и се случва **и на Apache, и на LiteSpeed**. Затова трите redirect-а (HTTPS, www, trailing slash) пренасочват към `/public/...`. Само `%{THE_REQUEST}` (суровият request line) никога не се променя — затова той е правилната основа за redirect-и. Потвърдено и с `/css` → `301 → /public/css/` (DirectorySlash на сървъра също изтича вътрешния път).
4. **Laravel вярва на URL-а.** При заявка `/public/weddings` Symfony изчислява `baseUrl = /public` и всички `url()`, `route()`, `asset()` генерират `/public/...` (78× `url(`, 23× `route(`, 113× `asset(` в blade файловете). При заявка `/weddings` базата е чиста — затова версията без `/public/` изглежда наред.
5. **Съществуващите „лепенки“ не решават проблема:**
   - [app/Http/Middleware/NormalizeCanonicalUrl.php](../app/Http/Middleware/NormalizeCanonicalUrl.php#L28-L33) проверява `$request->path()` за префикс `public/`, но Symfony вече е махнал `/public` от `path()` → клонът никога не се изпълнява (доказано: `/public/weddings` връща 200, не 301). Останалата част дублира HTTPS/www redirect-ите от `.htaccess`.
   - [resources/views/layouts/app.blade.php](../resources/views/layouts/app.blade.php#L36-L40) „закърпва“ canonical с `preg_replace('#^public/#', ...)` и hardcoded домейн — затова canonical е чист, а линковете не са.
   - [app/Http/Controllers/SitemapController.php](../app/Http/Controllers/SitemapController.php) е с hardcoded `$baseUrl` (commit `5fe1d05`) — симптоматично решение, което потвърждава, че проблемът е бил забелязан.

### A.3 Защо „преди беше директно името на услугата“

Старият статичен сайт имаше root `.htaccess` с абсолютни redirect-и (`https://taketwostudio1603.com/$1`) и без папка `public/`. При миграцията към Laravel root правилата бяха заменени с rewrite към `public/`, а redirect-ите се преместиха в `public/.htaccess`, където LiteSpeed ги „заразява“ с префикса.

### A.4 Препоръчано решение (резюме; детайли в Част C.1)

Един-единствен източник на истина за канонизацията — **committed root `.htaccess`**, който: (1) прави HTTPS/www/trailing-slash/`/public/`/`index.php`→чист redirect с **един** hop на базата на `%{THE_REQUEST}`, (2) блокира чувствителни пътища (`.env`, `.git`, `composer.*`, `vendor/`, `_legacy/`, `Ardes/`…), (3) накрая пренаписва вътрешно към `public/`. От `public/.htaccess` се махат всички redirect блокове. В Laravel: `URL::forceRootUrl()` + `forceScheme('https')` в production, `APP_URL` в `.env`, пренаписване на middleware-а да ползва `getBaseUrl()`, махане на `preg_replace` хака, `SitemapController` да ползва `config('app.url')`. Алтернативата „смяна на Document Root към `public_html/public`“ за primary домейн е read-only в cPanel (само хостингът през WHM може) — питай веднъж, но не разчитай на нея. Пълните файлове и редът на деплой са в Част C.1.

---

## Топ 10 действия по приоритет (резюме на целия план)

| # | Действие | Част | Ефект |
|---|---|---|---|
| 1 | Махни публичните `/seed-all`, `/test-email-send`, `public/optimize.php`; изтрий `Archive.zip`/`storage.zip`; махни чуждата Ardes страница от домейна | C.2 | сигурност + чистота на индекса |
| 2 | Root `.htaccess` + нов `public/.htaccess` + `forceRootUrl` + production `.env` (`APP_URL`) | C.1 | край на `/public/` дублирането, 1-hop redirect-и |
| 3 | Bing Webmaster Tools: импорт от GSC, sitemap, Site Explorer; IndexNow; Bing Places | D.7 | без Bing → без ChatGPT |
| 4 | Един NAP: телефон E.164, един имейл, `sameAs` с TikTok/YouTube/Maps, махни `aggregateRating`, `areaServed` списък | C.4.4, B9/B17/B19 | entity консистентност |
| 5 | GBP playbook + система за Google отзиви (най-висок ROI) | D.6 | Gemini/AI Overviews/Maps |
| 6 | Пренаписване на `/proms` по „answer capsule“ + нови `/ceni`, `/za-nas`, `/kontakti` | D.4, D.5 | цитируемо съдържание за AI; сезонът е сега |
| 7 | GPTBot 429 — диагноза + тикет към хостинга | C.3 | OpenAI training crawler (ChatGPT Search не е засегнат) |
| 8 | Един JSON-LD `@graph` (Organization/LocalBusiness/WebSite/WebPage/Breadcrumb/Service+Offer/FAQ/Person/Video/ImageObject), FAQ консолидация, реален `lastmod` + image sitemap | C.4 | чист entity граф |
| 9 | Скорост: hero WebP/AVIF + `<picture>`/srcset/lazy, Vite bundle вместо 7 CDN стила, без unpkg | C.5 | LCP < 2,5 s |
| 10 | Цитирания (mywedding.bg, weddingday.bg, starofservice.bg, НАПСФВ, зали-партньори) + YouTube шаблони + месечен AI панел (20 промпта) | D.8, D.10 | консенсус на трети страни, измерване |

---

## Част B — Инвентаризация на всички находки

### B.1 Критични (сигурност / индексиране)

| # | Находка | Доказателство | Къде |
|---|---|---|---|
| B1 | `GET /seed-all` е публичен и без auth → всеки може да пусне seed-ване на базата | live: **HTTP 200** | [routes/web.php:77](../routes/web.php#L77), `SeedController` |
| B2 | `GET /test-email-send` е публичен → при всяко отваряне създава запис `Inquiry` и праща имейл до админа (спам/DoS вектор, замърсява CRM-а) | live: **HTTP 200** | [routes/web.php:39-63](../routes/web.php#L39-L63) |
| B3 | Дублирано съдържание `/public/*` + redirect chains към `/public/*` (Част A) | live curl матрица | server root `.htaccess`, [public/.htaccess](../public/.htaccess) |
| B4 | **GPTBot получава HTTP 429** (празен body, `server: LiteSpeed`) в 9 от 9 мои теста през ~25 минути (08:56 и 09:18 UTC); агентът видя кратък прозорец с 200 → **UA-keyed rate limit с cooldown на ниво хостинг**, не hard block. robots.txt го разрешава. `OAI-SearchBot` (crawler-ът на ChatGPT Search) и `ChatGPT-User` минават с 200 → **видимостта в ChatGPT Search не е засегната**; засегнат е training crawler-ът. `Bytespider`, `Amazonbot`, `meta-externalagent` → 403 (host bot list) | live UA тестове | хостинг JetHosting (`ms.eu108.jethosting.com`): LiteSpeed per-client throttling / Imunify360 / ModSecurity |
| B4a | `public/optimize.php` е **публичен (200)** и без auth — `?run` стартира GD пренаписване на всички изображения (CPU DoS + променя файлове) | live 200 | [public/optimize.php:13](../public/optimize.php#L13) |
| B4b | `BlogPostSeeder` и `LegalPageSeeder` ползват `updateOrCreate` по slug → всяко посещение на `/seed-all` (вкл. HEAD) **презаписва редакции от админа** на seed-натите постове и правни страници | код | [BlogPostSeeder.php:30](../database/seeders/BlogPostSeeder.php#L30), [LegalPageSeeder.php:36](../database/seeders/LegalPageSeeder.php#L36) |
| B4c | Чужда клиентска страница (Ardes/NVIDIA landing) се сервира и е индексируема на този домейн: `/ardes/nvidia-02.2026/index.html` → 200; архиви в web-достъпни пътища: `/storage.zip` → 200, `storage/app/public/Archive.zip` (97 MB, 403 само заради host конфиг) | live | `public/ardes/`, `Ardes/`, `public/storage.zip` |
| B4d | `/favicon.ico` е **0 байта** (Google показва favicon в мобилни SERP); `/site.webmanifest` → **404** (линкнат от всяка страница); `home.blade.php:11` preload-ва несъществуващ `css/img/header.webp` (404 при всяко зареждане); липсват `social-share-cover.jpg` (default `og:image` за блога → 404), `best-wedding-cover.jpg`, `default-placeholder.jpg` | live + repo | [layouts/app.blade.php:75](../resources/views/layouts/app.blade.php#L75), [home.blade.php:11](../resources/views/home.blade.php#L11) |

### B.2 Високи (SEO съдържание и структура)

| # | Находка | Къде |
|---|---|---|
| B5 | На живия `/weddings` **няма FAQ секция и FAQPage schema** (кодът ги поддържа, но таблицата `wedding_faqs` е празна в production). FAQPage има само на `/proms` | [resources/views/weddings.blade.php:647](../resources/views/weddings.blade.php#L647), [PageController.php:122-125](../app/Http/Controllers/PageController.php#L122-L125) |
| B6 | Няма самостоятелни страници **За нас / Контакти / Цени / Екип** (само anchors на home) → слаб E-E-A-T, няма „цени“ URL, а конкурентите имат | [routes/web.php](../routes/web.php) |
| B7 | Английски slugs (`/weddings`, `/proms`) срещу конкуренти с `/svatben-fotograf-varna/`, `/abiturienski-fotosesii-varna/` | routes |
| B8 | `aggregateRating` върху `LocalBusiness` от собствени testimonials → Google го смята за self-serving и не показва звезди; риск за spam флаг | [layouts/app.blade.php:120-126](../resources/views/layouts/app.blade.php#L120-L126) |
| B9 | Несъответствие на телефона: `088 619 0124` (сайт, JSON-LD като `0886190124`, не е E.164) срещу `089 420 0634` (proms + mobile CTA, commit `ff893d0`) → NAP несъответствие за GBP/AI | [proms.blade.php](../resources/views/proms.blade.php), [partials/mobile-sticky-cta.blade.php](../resources/views/partials/mobile-sticky-cta.blade.php) |
| B10 | Липсва `BreadcrumbList`, `VideoObject`, `Person` (екип), `Offer` с цени (има само на мъртвата `/graduation`), няма общ `@id` граф Organization↔WebSite↔WebPage | views |
| B11 | Sitemap: `lastmod` = днешна дата за всички статични URL (фалшива свежест), `changefreq/priority` (игнорирани), без `/blog/category/*` | [SitemapController.php](../app/Http/Controllers/SitemapController.php) |
| B12 | robots.txt изброява `/clear-cache` и `/force-login` → рекламира вътрешни endpoints | [public/robots.txt](../public/robots.txt) |
| B13 | **Сайтът не се появява в Bing** — нито `site:taketwostudio1603.com`, нито брандовата заявка `taketwostudio1603` връщат резултат („Няма резултати“). ChatGPT Search стъпва на индекса на Bing → докато това не се оправи, ChatGPT няма как да цитира сайта | Bing Webmaster Tools (проверка на покритието) |
| B14 | Title тагове над 60 знака на 5 от 9 услуги (proms 82, commercial 82, portrait 94, baptism 74, weddings 62); description над 155 знака на 6 страници (commercial 195, portrait 179, proms 172) → отрязват се в SERP | `@section('title')` / `meta_description` във всяка service view |
| B15 | H1 на услугите е без ключова дума и град („Вашият Сватбен Ден“, „Абитуриентски Балове“, „Реклама и Бизнес“) → H1 ≠ заявка, докато title е правилен | service views |
| B16 | Началната страница емитира **два** отделни `LocalBusiness` блока (site-wide + собствен) без общ `@id` → две „фирми“ за парсера | [layouts/app.blade.php:83](../resources/views/layouts/app.blade.php#L83), [home.blade.php:306-345](../resources/views/home.blade.php#L306-L345) |
| B17 | `sameAs` съдържа само Facebook и Instagram; YouTube и TikTok липсват в schema и във footer-а (footer линква 3 лични Instagram профила на екипа) → по-слаба entity връзка към каналите, които Gemini/ChatGPT цитират | [layouts/app.blade.php:116-119](../resources/views/layouts/app.blade.php#L116-L119), footer |
| B18 | `aggregateRating` е `5.0` от `3` отзива → освен self-serving (B8), малкият брой прави сигнала слаб; реалната стойност е в Google отзивите | Testimonial таблица |
| B19 | `/proms` показва **друг имейл** (`info@taketwostudio1603.com`) спрямо останалия сайт (`taketwostudio1603@gmail.com`) + втори телефон (B9) → NAP на най-важната страница се разминава с GBP/schema | [proms.blade.php](../resources/views/proms.blade.php) |
| B20 | Блог постът `/blog/speistete-budjet-balno-zasnemane-varna` рекламира „195 лв. на ученик“ — противоречи на актуалните € цени (100/120 €) на `/proms` и в llms-full.txt → объркващ сигнал за AI и клиенти | [database/seeders/BlogPostSeeder.php:168-175](../database/seeders/BlogPostSeeder.php#L168-L175), production DB |
| B21 | Blog `BlogPosting.author` е `Organization`, не `Person` → няма E-E-A-T авторска връзка | [blog/show.blade.php:20-24](../resources/views/blog/show.blade.php#L20-L24) |
| B22 | `areaServed` в schema е само „Варна“, докато услугите се предлагат и в Добрич/Шумен/Балчик/Каварна/Бяла | [layouts/app.blade.php:105-109](../resources/views/layouts/app.blade.php#L105-L109) |

### B.3 Скорост (live `/weddings`, mobile)

| Метрика | Стойност |
|---|---|
| HTML | 120 KB |
| Stylesheets | 7 (Bootstrap CDN, FontAwesome CDN, Google Fonts, glightbox CDN, **AOS от unpkg.com**, style.css, weddings.css) |
| Външни скриптове | 5 (gtag, bootstrap.bundle CDN, **aos от unpkg**, glightbox CDN, wedding.js) |
| `<img>` общо / с `loading="lazy"` | 56 / **2** |
| Референции `.jpg` / `.webp` | **57 / 3** |
| Vite 7 + Tailwind 4 | инсталирани в `package.json`, но публичните страници не ги ползват |

PageSpeed API квотата беше изчерпана днес — Lighthouse числа трябва да се снемат ръчно (виж Част E).

### B.4 Поддръжка / хигиена

- Десетки дублирани файлове с суфикс `" 2"` (macOS copy артефакти) в `app/`, `resources/views/`, `database/` — шум и риск от объркване при деплой.
- 5 отделни FAQ таблици (`wedding_faqs`, `prom_faqs`, …) + неизползвана обща `faqs`; 10 отделни пакетни таблици → schema/FAQ логиката се копира на ръка във всяка страница и се разминава (B5, B10).
- `PageController` има по един hardcoded метод за услуга; `PortfolioCategory` slug без route дава мъртъв линк в навигацията.
- `.env`: `APP_LOCALE=en` при изцяло български сайт (влияе на `Carbon`/дати в blog).

---

## Част C — Технически план (какво, къде, как)

`$ROOT` = `/Users/pgg/Documents/GitHub/TakeTwoStudio`; сървър: `/home/mbgsqksf/public_html` = `$ROOT`. Хост: JetHosting (`ms.eu108.jethosting.com`), cPanel + LiteSpeed, HTTP/2 + HTTP/3 активни.

### C.0 ⚠️ Странични ефекти от днешния анализ (за проверка още сега)

Проверките бяха HTTP GET/HEAD заявки, но три endpoint-а на сайта имат странични ефекти при GET — точно затова са в „критични“:

| Какво | Кой/кога | Ефект | Какво да направиш |
|---|---|---|---|
| `GET /seed-all` | аз (~08:50 UTC) и техническият агент (HEAD, ~09:05 UTC) | `SeedController::run()` се изпълни 2 пъти. Seeders-ите са guard-нати (`count()===0` / `updateOrCreate`) → без дублирани редове, **но** `BlogPostSeeder`/`LegalPageSeeder` презаписват по slug → ако seed-нати блог постове или правни страници са били редактирани в Filament след първоначалния seed, редакциите може да са върнати към seed версията | Отвори Filament → Blog Posts и Legal Pages и провери 2–3 редактирани записа. |
| `GET /test-email-send` | аз (~08:50 UTC) | Създаден е запис `Inquiry` „Тестов Клиент (Автоматичен Тест)“ и е изпратен имейл до админския адрес | Изтрий записа от Filament → Inquiries. |
| `HEAD /check_video_path.php` | агентът | Файлът е самоизтриващ се stub (`@unlink(__FILE__)`) → изчезнал е от `public/` на сървъра; още е в git (pull го връща) | Изтрий го от репото. |

### C.1 Окончателен fix на `/public/`

**Опции**

| Опция | Как | Плюс | Минус | Решение |
|---|---|---|---|---|
| (a) Document Root → `public_html/public` | cPanel › Domains; за primary домейн полето е read-only — само хостингът (WHM) | най-чисто, stock Laravel | зависи от хоста | питай веднъж (безплатно); ако се съгласят — правилата от блок 2 отиват в `public/.htaccess`, root файлът се маха |
| **(b) Committed root `.htaccess`** | този план | работи с cPanel Git in-place checkout, без зависимост от хоста, версиониран, една точка за канонизация | `.env`/`vendor` остават в webroot (смекчено с deny правила) | **основен път** |
| (c) App над webroot, `public/` копиран/symlink-нат в `public_html` | нов cPanel repo в `~/app`, `.cpanel.yml` копира `public/*`, патч на `index.php` пътища | най-добра сигурност | конфликт с текущия in-place checkout; `storage:link`, Filament uploads, деплой се променят | при следваща смяна на хостинг |

**Нов файл `$ROOT/.htaccess` (root, committed):**

```apache
# ------------------------------------------------------------------------
# Take Two Studio - project-root .htaccess (Laravel root == cPanel docroot)
# Single source of truth for: deny rules, canonical 301s, rewrite into public/.
# All redirect decisions use %{THE_REQUEST} (raw request line), which internal
# rewrites never modify -> no /public/ leaks, no loops. Apache + LiteSpeed.
# ------------------------------------------------------------------------
<IfModule mod_negotiation.c>
    Options -MultiViews -Indexes
</IfModule>

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /

# 0) cPanel AutoSSL / ACME validation must never be rewritten or denied
RewriteRule ^\.well-known/ - [L]

# 1) Deny project internals (belt and braces; the catch-all below already hides them)
#    NOTE: /storage/... is NOT denied by URL - it is the public symlink for uploads.
RewriteRule ^\.(env|git|claude|editorconfig|phpunit) - [F,L]
RewriteRule (^|/)\.DS_Store$ - [F,L]
RewriteRule ^(_legacy|Ardes|app|bootstrap|config|database|docs|resources|routes|tests|vendor|node_modules)(/|$) - [F,L]
RewriteRule ^storage/(app/private|framework|logs)(/|$) - [F,L]
RewriteRule ^(artisan|composer\.(json|lock)|package(-lock)?\.json|phpunit\.xml|vite\.config\.js|README\.md|[^/]+\.(py|sql|sh|bak|log))$ - [F,L]

# 2) ONE 301 to the canonical URL (GET/HEAD only) when ANY of:
#    http, www, /public prefix, index.php, trailing slash.
#    Query string is preserved automatically (no "?" in the substitution).
RewriteCond %{REQUEST_METHOD} ^(GET|HEAD)$
RewriteCond %{HTTP_HOST} ^(www\.)?taketwostudio1603\.com$ [NC]
RewriteCond %{HTTPS} !=on [OR]
RewriteCond %{HTTP_HOST} ^www\. [NC,OR]
RewriteCond %{THE_REQUEST} \s/public(?:/|[\s?]) [NC,OR]
RewriteCond %{THE_REQUEST} \s/(?:public/)?index\.php(?:[\s?/]) [NC,OR]
RewriteCond %{THE_REQUEST} \s/[^\s?]+/(?:[\s?])
RewriteCond %{THE_REQUEST} ^[A-Z]+\s/+(?:public(?:/+|(?=[\s?])))?(?:index\.php(?:/+|(?=[\s?])))?([^\s?]*?)/*[\s?] [NC]
RewriteRule ^ https://taketwostudio1603.com/%1 [R=301,L,NE]

# 3) URLs that resolve to a real directory inside public/ (/css, /js, /storage/blog ...)
#    are never content. 403 here so the server's DirectorySlash redirect can never
#    leak "/public/css/" again (today: /css -> 301 -> /public/css/).
RewriteCond %{REQUEST_URI} !^/$
RewriteCond %{DOCUMENT_ROOT}/public%{REQUEST_URI} -d
RewriteRule ^ - [F,L]

# 4) Everything else -> Laravel public/ (public/.htaccess runs the front controller)
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Бележки: regex-ите в блок 2 са валидирани с PCRE срещу 20 гранични случая (`/public`, `/public/`, `/public//weddings//`, `/index.php/blog/x`, `/publicity` → не се пипа, кирилски файлови имена → запазени заради `NE`, `?page=2` → запазен). `%1` идва от последния съвпаднал `RewriteCond`; оценката е `C1 && C2 && (C3||C4||C5||C6||C7) && C8`. Host guard-ът (`C2`) прави файла неактивен на localhost/XAMPP. Ако някога се сложи Cloudflare/proxy отпред: замени `RewriteCond %{HTTPS} !=on` с `RewriteCond %{HTTP:X-Forwarded-Proto} !https`.

**Нов `$ROOT/public/.htaccess` (замества целия файл):**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Only the front controller may execute PHP inside public/
    RewriteRule ^(?!index\.php$).+\.php$ - [F,L]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/avif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
</IfModule>

# Vite hashed assets are immutable
<IfModule mod_headers.c>
    <FilesMatch "^.+-[A-Za-z0-9_-]{8}\.(css|js|woff2)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
</IfModule>
```

Премахнати от `public/.htaccess`: HTTPS блокът, WWW блокът и trailing-slash блокът (редове 9–14 и 25–28 на текущия файл).

**Laravel промени**

| # | Файл | Промяна |
|---|---|---|
| 1 | [app/Providers/AppServiceProvider.php](../app/Providers/AppServiceProvider.php) `boot()` | `if (! $this->app->environment('local', 'testing')) { URL::forceRootUrl(config('app.url')); URL::forceScheme('https'); }` — `url()/asset()/route()/Storage::url()` стават имунни на това как е дошла заявката |
| 2 | Production `.env` (cPanel File Manager, `public_html/.env`) | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://taketwostudio1603.com`. `config/filesystems.php:44` извлича `/storage` URL-ите от `APP_URL` → Filament preview-ите също зависят. После `php artisan config:clear`. Коментар в `.env.example` |
| 3 | [app/Http/Middleware/NormalizeCanonicalUrl.php](../app/Http/Middleware/NormalizeCanonicalUrl.php) | Пази се като fallback (ако `.htaccess` се загуби), но пренаписан: GET/HEAD; skip в `local`/`testing`; `$root = rtrim(config('app.url'),'/')`; 301 към `$root.'/'.trim($request->getPathInfo(),'/').($qs?'?'.$qs:'')` при: host ≠ host на `APP_URL`, `! isSecure()`, **`$request->getBaseUrl() !== ''`** (правилният детектор за `/public` и `/index.php`), или trailing slash. Остава `prepend()` |
| 4 | [layouts/app.blade.php:36-40](../resources/views/layouts/app.blade.php#L36-L40) | `preg_replace` блокът → `$canonicalUrl = url()->current();` (за blog `?page>1` → `url()->full()`, self-canonical) |
| 5 | [SitemapController.php](../app/Http/Controllers/SitemapController.php) | `$baseUrl = rtrim(config('app.url'), '/');` |
| 6 | всички `@push('schema')` с hardcoded `'https://taketwostudio1603.com...'` (weddings 706-721, proms 397-412, baptism 469-473, commercial 274-278, family 363-368, portrait 342-347, automotive 333-338, architectural 343-348, events 275-280, graduation 585-591) + [home.blade.php:306-345](../resources/views/home.blade.php#L306-L345) | `url('/')` / `url()->current()`; после се заменят от graph partial-а (C.4) |
| 7 | `tests/Feature/CanonicalUrlTest.php` (нов) | `$this->call('GET','/weddings',[],[],[],['SCRIPT_NAME'=>'/public/index.php','REQUEST_URI'=>'/public/weddings','HTTPS'=>'on','HTTP_HOST'=>'taketwostudio1603.com'])->assertRedirect('https://taketwostudio1603.com/weddings')`; `url('/') === config('app.url')`; рендерираният `/weddings` не съдържа `/public/` |

**Ред на деплой (без 404/loop) и rollback**

Pre-flight (SSH `ssh mbgsqksf@ms.eu108.jethosting.com` или cPanel Terminal):
```bash
cd ~/public_html
cat .htaccess                 # запази ръчния файл; сложи копие в репото като _legacy/.htaccess_server_2026-09
git status --porcelain        # трябва да покаже САМО "?? .htaccess"; всичко друго → реши първо
ls bootstrap/cache/           # ако има config.php/routes-v7.php → кешовете се ползват
which php; php -v
```
1. **Commit A** („canonical URL fix“): root `.htaccess`, нов `public/.htaccess`, `AppServiceProvider`, `NormalizeCanonicalUrl`, canonical в layout, `SitemapController`, `.env.example`, тестът. Merge в `laratake`.
2. **Server `.env`** първо (безопасно самостоятелно): `APP_ENV`, `APP_DEBUG`, `APP_URL`.
3. **Switch** (под секунда; в тих час). Git отказва pull върху untracked `.htaccess`, затова в една команда:
   ```bash
   cd ~/public_html && cp .htaccess ~/htaccess.pre-fix.bak && rm .htaccess && git pull --ff-only origin laratake && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
   Без SSH: File Manager → преименувай `.htaccess` на `.htaccess.pre-fix` → веднага cPanel › Git Version Control › Manage › Pull or Deploy › „Update from Remote“ (прозорец от няколко секунди, в който `/` дава 403, не loop). Алтернатива без колизия: пази файла като `deploy/root.htaccess` и root `.cpanel.yml` го копира при „Deploy HEAD Commit“.
4. **Verify** веднага с матрицата долу (20 s).
5. **Rollback:** `cd ~/public_html && cp ~/htaccess.pre-fix.bak .htaccess && git checkout HEAD~1 -- public/.htaccess && php artisan optimize:clear` (PHP промените са безопасни да останат).
6. Root `.cpanel.yml` за бъдещи деплои (опц.): tasks `php artisan optimize:clear` + `php artisan optimize`.

**Матрица на redirect-ите след деплой**

| Заявка | Очаквано |
|---|---|
| `http://taketwostudio1603.com/weddings` | 301 → `https://taketwostudio1603.com/weddings` (1 hop) |
| `http://www.taketwostudio1603.com/public/weddings/` | 301 → чист URL (**1 hop**, не 3) |
| `https://www.taketwostudio1603.com/weddings` | 301 → чист |
| `https://taketwostudio1603.com/weddings/` | 301 → `/weddings` |
| `https://taketwostudio1603.com/public/weddings` | 301 → `/weddings` |
| `/public`, `/public/`, `/index.php`, `/public/index.php` | 301 → `https://taketwostudio1603.com/` |
| `/index.php/blog/x` | 301 → `/blog/x` |
| `/public/weddings?x=1` | 301 → `/weddings?x=1` |
| `/weddings?utm_source=x` | 200, canonical = `https://taketwostudio1603.com/weddings` |
| `/css/style.css`, `/storage/<upload>` | 200 |
| `/css` | 403 (беше 301 → `/public/css/`) |
| `/.env`, `/composer.json`, `/vendor/autoload.php`, `/storage/logs/laravel.log`, `/_legacy/index.html`, `/Ardes/…`, `/.git/config` | 403 |
| `/optimize.php` | 403 (404 след изтриване) |
| `POST /submit-contact` | никога 301 (method guard) |
| `/admin` | 302 → `/admin/login`; `/up` 200 |

```bash
for u in http://taketwostudio1603.com/weddings "http://www.taketwostudio1603.com/public/weddings/" https://www.taketwostudio1603.com/weddings https://taketwostudio1603.com/weddings/ https://taketwostudio1603.com/public/weddings https://taketwostudio1603.com/public https://taketwostudio1603.com/public/ https://taketwostudio1603.com/index.php https://taketwostudio1603.com/public/index.php "https://taketwostudio1603.com/public/weddings?x=1" https://taketwostudio1603.com/css https://taketwostudio1603.com/.env https://taketwostudio1603.com/composer.json https://taketwostudio1603.com/vendor/autoload.php https://taketwostudio1603.com/storage/logs/laravel.log; do printf "%-62s " "$u"; curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" "$u"; done
curl -sIL -o /dev/null -w "%{num_redirects} hops -> %{url_effective} %{http_code}\n" "http://www.taketwostudio1603.com/public/weddings/"   # очаква 1 hop
curl -s https://taketwostudio1603.com/weddings | grep -o 'href="[^"]*"' | grep -c '/public/'   # очаква 0
curl -s -o /dev/null -w "%{http_code}\n" -X POST https://taketwostudio1603.com/submit-contact   # 419/422, никога 301
curl -sIL --max-redirs 5 -o /dev/null -w "%{num_redirects}\n" https://taketwostudio1603.com/css   # без loop
```

**Google Search Console след деплой:** Domain property; URL Inspection → „Request indexing“ за всяка услуга; търси `site:taketwostudio1603.com inurl:public` и `site:www.taketwostudio1603.com` → при резултат: Removals → „Remove all URLs with this prefix“ `https://taketwostudio1603.com/public/`; resubmit sitemap; следи Pages report — `/public/` и `www` вариантите трябва да станат „Page with redirect“ до 2–6 седмици.

### C.2 Сигурност и хигиена

| Елемент | Къде | Действие | Проверка |
|---|---|---|---|
| `/test-email-send` | [routes/web.php:39-63](../routes/web.php#L39-L63) | Изтрий. Ако трябва → artisan команда `mail:test-inquiry` | 404 |
| `/seed-all` | [routes/web.php:77](../routes/web.php#L77), `SeedController` | Изтрий route + controller; `php artisan db:seed --class=…` през SSH | 404 |
| `/clear-cache` | [routes/web.php:65-75](../routes/web.php#L65-L75) | Изтрий (или `middleware(['auth'])` + `isAdmin()`); махни от robots.txt | 404/403 |
| `/force-login` | web.php:92-106 | вече local-only; махни от robots.txt | – |
| `public/optimize.php` (200, `?run` пренаписва изображения) | `public/optimize.php` | Изтрий; новото `public/.htaccess` блокира всеки не-`index.php` PHP | 403/404 |
| `public/check_video_path.php`, `public/final_cleanup.php` | `public/` | Изтрий от репото | – |
| `storage/app/public/Archive.zip` (97 MB), `public/storage.zip` | сървър + репо | `rm`; `git rm public/storage.zip` | 404 |
| Чужда страница `public/ardes/nvidia-02.2026/` + root `Ardes/` | репо | Махни от това репо; временно: `X-Robots-Tag: noindex, nofollow` в `public/ardes/.htaccess` | 404 |
| Dev скриптове в root (`add_groups.php`, `seed_proms.php`, `test_mail.php`, `optimize_images.php/.py`, `_legacy/` с `database_schema.sql`, `submit_order*.php`) | `$ROOT` | Премести в `scripts/` (denied) или изтрий; `_legacy` → git tag | 403 |
| Дубликати `" 2"` (52 tracked + 46 untracked) | repo-wide | Прегледай: `find $ROOT -path '*/vendor' -prune -o -path '*/node_modules' -prune -o -path '*/.git' -prune -o -name '* [0-9]*' -print \| sort`; после `git ls-files -z \| grep -zE ' [0-9]\.' \| xargs -0 git rm --` и `find … -name '* [0-9]*' -exec rm -rf {} +`; също `bootstrap/cache/packages 2.php`, `services 2.php`, `services 3.php` | `git ls-files \| grep -c ' 2\.'` → 0 |
| `.claude/settings.local.json` tracked | `.claude/` | `echo ".claude/" >> .gitignore && git rm -r --cached .claude` | – |
| `/api/booking-availability`, `/api/booking-hours` без throttle | web.php:85-86 | `->middleware('throttle:60,1')` | 429 след 60/min |
| robots.txt | `public/robots.txt` | Махни `/clear-cache`, `/force-login`; **не** добавяй `Disallow: /public/` (пречи на Google да види 301-ите); сгъни 8-те идентични групи в една `User-agent: *`; `Claude-Web` е остарял token | curl |
| Security headers (няма) | root `.htaccess` | опц.: `X-Content-Type-Options nosniff`, `Referrer-Policy strict-origin-when-cross-origin`, `Permissions-Policy`, HSTS `max-age=31536000` (след зелена redirect матрица) | `curl -sI` |

### C.3 GPTBot 429 — диагноза и отстраняване

Известно: UA-keyed throttle с cooldown, само на динамични (PHP) отговори, не на статични файлове; OAI-SearchBot и ChatGPT-User никога не са блокирани → ChatGPT Search не е засегнат. Приоритет: среден.

| Стъпка | Къде | Какво търсим |
|---|---|---|
| 1. Възпроизвеждане от 2 мрежи (домашна + телефон/VPS) | терминал | `for i in $(seq 1 20); do date -u +%T; curl -s -o /dev/null -w "%{http_code}\n" -A "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.2; +https://openai.com/gptbot" https://taketwostudio1603.com/weddings; sleep 1; done`. Само една мрежа с 429 → IP+UA keyed. Body на 429: LiteSpeed error page = сървър; Blade = app; Imunify splash = WAF |
| 2. Access logs | cPanel › Metrics › Raw Access → `taketwostudio1603.com-ssl_log` | `zgrep -i gptbot *ssl_log* \| awk '{print $9}' \| sort \| uniq -c`. 429 в лога → LiteSpeed/PHP в vhost-а; 429 при клиента, но липсва в лога → upstream (Imunify360 WebShield / host proxy) |
| 3. Ръчният root `.htaccess` на сървъра | `grep -inE "gptbot\|user_agent\|429" ~/public_html/.htaccess` | UA правило (малко вероятно) |
| 4. ModSecurity | cPanel › Security › ModSecurity (+ Hits List) | rule ID при GPTBot UA; изключи за домейна 10 мин и повтори стъпка 1 |
| 5. Imunify360 | cPanel › Imunify360 › Proactive Defense / Incidents / Anti-bot | записи за UA/IP; incident ID за support |
| 6. cPanel tools | Leech Protection, IP Blocker, Hotlink Protection | нищо за GPTBot |
| 7. LiteSpeed server-level (само WHM) | хостингът | Per-Client Throttling (`dynReqPerSec`), reCAPTCHA/Bot protection, Bot Whitelist, AI-crawler политика |

**Тикет към JetHosting (шаблон):** „На <UTC часове> заявки с User-Agent `GPTBot/1.2 (+https://openai.com/gptbot)` към `https://taketwostudio1603.com/weddings` получават HTTP 429, докато други crawler-и получават 200. Кой слой връща 429 (LiteSpeed per-client throttling, Imunify360, ModSecurity rule ID или AI-crawler политика)? Моля, изключете публикуваните IP диапазони на OpenAI за нашия акаунт или вдигнете лимита: GPTBot `https://openai.com/gptbot.json`, OAI-SearchBot `https://openai.com/searchbot.json`, ChatGPT-User `https://openai.com/chatgpt-user.json`.“ Диапазони: `curl -s https://openai.com/gptbot.json | jq -r '.prefixes[] | .ipv4Prefix // .ipv6Prefix'`. Ако е ModSecurity: cPanel › ModSecurity › disable rule ID за домейна, или в root `.htaccess`: `<IfModule mod_security2.c> SecRuleRemoveById <id> </IfModule>`.

**Верификация:** 20×200 от две мрежи; след седмица в лога само 200/301/304 за GPTBot.

### C.4 Structured data и on-page инфраструктура

**Реалистични очаквания:** Google показва FAQ rich results само за държавни/здравни сайтове (от 08.2023); няма rich result за `Service`/`Offer`; self-serving `aggregateRating` върху `LocalBusiness` е неприемлив за review snippets; видео thumbnail изисква видеото да е основното съдържание. **Какво се отплаща:** чист entity граф (Knowledge Panel/AI Overviews/LLM отговори), `BreadcrumbList` (SERP breadcrumbs), `Organization` logo, `ImageObject` с лиценз (Google Images „Licensable“ бадж — много релевантно за фото студио), `Person` (E-E-A-T), image sitemap, `BlogPosting`.

**C.4.1 Един граф, един `<script>`**

| Файл | Цел |
|---|---|
| `app/Support/SchemaGraph.php` (нов, singleton в `AppServiceProvider::register`) | `add(array $node)`, `nodes()`. Views/controllers добавят възли преди layout-а да рендерира |
| `resources/views/partials/schema-graph.blade.php` (нов) | емитира **един** `{"@context":"https://schema.org","@graph":[...]}`; замества [app.blade.php:78-143](../resources/views/layouts/app.blade.php#L78-L143); `@stack('schema')` остава един release за миграция |
| Core възли | `#organization` (Organization: name, url, logo ImageObject, `sameAs` от settings, `contactPoint[]`), `#localbusiness` (["LocalBusiness","ProfessionalService"]: `parentOrganization`→`#organization`, address, geo `43.21405/27.914733` (един източник), openingHours, priceRange, `areaServed` списък, image, `telephone` E.164, **без aggregateRating**), `#website` (WebSite: url, name, `inLanguage: bg`, publisher), `#webpage` (WebPage: `@id = url()->current().'#webpage'`, name = title, description, `isPartOf`, `about`→`#localbusiness`, `breadcrumb`→`#breadcrumb`, `primaryImageOfPage`) |
| `app/Support/Seo/Breadcrumbs.php` + `partials/breadcrumbs.blade.php` (нови; видим `<nav aria-label="breadcrumb">` под hero-то на всяка не-home страница) | от route: `/`→[Начало]; услуга→[Начало, `Service.name_bg`]; blog.index→[Начало, Блог]; blog.category→[…, категория]; blog.show→[…, категория, пост]; booking/legal→[Начало, title]. Емитира `BreadcrumbList` `#breadcrumb` с абсолютни `item` URL |

**C.4.2 Service страници (всички 9)**
- `Service` блоковете от views → `PageController::showService()` → `SchemaGraph::add()` веднъж: `provider: {"@id":"#localbusiness"}`, `url: url($slug)`, `serviceType`, `areaServed`, `offers`.
- **Offers** от пакетните таблици по slug (`service_packages.price_eur` за weddings/baptism/commercial; `PromPackage`, `FamilyPackage`, `PortraitPackage`, `AutomotivePackage`, `ArchitecturalPackage`, `EventPackage`): `{"@type":"Offer","name":…,"price":"450.00","priceCurrency":"EUR","availability":"https://schema.org/InStock","eligibleRegion":{"@type":"Country","name":"BG"},"url":url($slug).'#packages'}`; „от“ цени → `UnitPriceSpecification` с `minPrice`. `PackageSource` map вместо `if ($slug === …)` блоковете в [PageController.php:120-182](../app/Http/Controllers/PageController.php#L120-L182).
- **FAQ консолидация (препоръчано):** неизползваната таблица `faqs` вече има `page_slug`, `question_bg`, `answer_bg`, `display_order`, `is_active` + `FaqResource` във Filament. Миграция `consolidate_faqs`: копира `wedding_faqs`→`weddings`, `prom_faqs`→`proms`, `baptism_faqs`→`baptism`, `commercial_faqs`→`commercial`, `graduation_faqs`→`proms`; `Faq::scopeForPage($slug)`; `PageController` подава `$faqs`; нов `partials/faq-section.blade.php` замества 5-те accordion копия; после drop 5 таблици, 5 модела, 5 Filament ресурса; update `LLMController::full`. Fallback при малко време: `app/Support/Seo/FaqSource.php` map slug→model. Live `/weddings` няма FAQ, защото `wedding_faqs` е празна в production → съдържанието се добавя през Filament след консолидацията.
- FAQ markup: `#webpage` става `@type: ["WebPage","FAQPage"]` с `mainEntity` (без отделен възел).

**C.4.3 Други entity-та**

| Entity | Източник / промяна | Къде |
|---|---|---|
| `VideoObject` | `services.video_url` (вече се парсва в [partials/video-showcase-section.blade.php:1-33](../resources/views/partials/video-showcase-section.blade.php#L1-L33)). Миграция: `services.video_uploaded_at`, `video_title`, `video_thumbnail` (YouTube default `https://i.ytimg.com/vi/{id}/maxresdefault.jpg`) + Filament полета. Възел: name, description, thumbnailUrl, uploadDate, embedUrl, publisher. Пропусни Instagram embeds | partial → `SchemaGraph::add()` |
| `Person` (E-E-A-T) | `team_members` (name, role_bg, bio_bg, image_path, instagram_url). Възли `url('/').'#person-'.$id`, jobTitle, image, worksFor, `sameAs:[instagram_url]`, knowsAbout. `Organization.employee`. Ново `blog_posts.author_team_member_id` (nullable FK) + Filament select; `BlogPosting.author` → Person (fallback `#organization`) | [home.blade.php](../resources/views/home.blade.php) team секция, [blog/show.blade.php:11-41](../resources/views/blog/show.blade.php#L11-L41) |
| `ImageObject` | hero + първите N галерийни снимки: contentUrl, width/height, caption (alt), `creator`→`#organization`, `creditText: "Take Two Studio 1603"`, `copyrightNotice`, `license: route('legal.terms')`, `acquireLicensePage: url('/kontakti')` → Google Images licensing бадж | от `<x-picture>` компонента (C.5) с `:schema="true"` |
| `BlogPosting` | + `author` Person, `publisher`, `mainEntityOfPage`→`#webpage`, `image` ImageObject, `wordCount`, `articleSection`, `inLanguage` | blog/show |
| Премахване | вторият `LocalBusiness` на home ([home.blade.php:306-345](../resources/views/home.blade.php#L306-L345) — конфликтни geo `43.2141/27.9147` и адрес); всички вложени `provider` копия с различни телефони; `aggregateRating` (layout 120-126) | – |

**C.4.4 NAP — един източник на истина**
- Нов `app/Support/Settings.php`: `Settings::get('site_phone')` върху `Cache::rememberForever('site_settings', fn () => SiteSetting::pluck('setting_value','setting_key'))`; `SiteSettingObserver` (`saved/deleted`) чисти кеша. Заменя всички `SiteSetting::find(4|5|6|7|8|14)` (layout 205-239, home 218-222/327-328, mobile-sticky-cta:7) и недетерминираното `where('site_phone')->orWhere('contact_phone')->first()` (layout 91,95,117,118).
- Данни: `site_phone = +359886190124` (E.164); `site_phone_secondary = +359894200634`, `site_phone_secondary_label = "Абитуриентски балове"`; изтрий `contact_phone/contact_email/contact_address` редовете от `LegacyDataSeeder.php:31-45`; `site_email` = реално наблюдаваният адрес (имейл шаблоните hardcode-ват `info@taketwostudio1603.com` в `emails/booking_confirmed.blade.php:29`, `booking_rejected.blade.php:17-18`, докато settings казват `taketwostudio1603@gmail.com`).
- `Settings::phoneDisplay('+359886190124')` → `088 619 0124`; `tel:` линкове с E.164.
- Schema: `#localbusiness.telephone = +359886190124`; `#organization.contactPoint = [{customer service, +359886190124, areaServed BG, availableLanguage [bg,en]}, {sales, "Абитуриентски балове", +359894200634}]`. **Бизнес решение:** ако номерът за балове е личен телефон на член от екипа → `Person.telephone`, а на страницата за балове остава един фирмен номер.
- Hardcoded телефони за поправка: `proms.blade.php:377-378,398`, `weddings.blade.php:707`, `graduation.blade.php:586`, `mobile-sticky-cta.blade.php:7`, `LLMController.php:28,48`, двата имейл шаблона.

**C.4.5 Sitemaps** ([SitemapController.php](../app/Http/Controllers/SitemapController.php))
- `/sitemap.xml` → `<sitemapindex>` с `/sitemap-pages.xml`, `/sitemap-blog.xml`, `/sitemap-images.xml`.
- Pages: `lastmod` = max(`services.updated_at`, `page_contents.updated_at`, пакетни таблици, `faqs.updated_at`) чрез `lastmodFor($slug)`; home = max от всички; `/booking` и правните страници (index,follow) се включват. Drop `changefreq`/`priority`. `/blog/category/*` вече е включен — остава.
- Images: `<image:image><image:loc>` за галерийни снимки по URL на услуга (до 1000), `xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"`.
- `Cache-Control: public, max-age=3600` за sitemap и `/llms*.txt`.

### C.5 Скорост (измеримо)

**Цели (mobile):** LCP < 2,5 s, CLS < 0,1, INP < 200 ms, TBT < 200 ms, `/weddings` above-the-fold < 1,5 MB, ≤ 2 render-blocking stylesheets, 0 third-party CSS/JS origins освен GA.

Базова линия (PSI квотата се нулира дневно; с API key):
```bash
curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://taketwostudio1603.com/weddings&strategy=mobile&category=performance&key=$PSI_KEY" | jq '.lighthouseResult.audits["largest-contentful-paint"].displayValue, .lighthouseResult.audits["cumulative-layout-shift"].displayValue, .lighthouseResult.audits["total-blocking-time"].displayValue, .lighthouseResult.categories.performance.score'
npx lighthouse https://taketwostudio1603.com/weddings --preset=perf --form-factor=mobile --screenEmulation.mobile --throttling-method=simulate --output=json --output-path=./lh-weddings-before.json --chrome-flags="--headless=new"
```

**C.5.1 Изображения** (най-големият лост: LCP hero `header.jpg` = 739 KB; 230 jpg / 15 webp на диска; `public/css/img/ads/` има 9 MB jpg/png и 8,4 MB „webp“)

| Стъпка | Файлове |
|---|---|
| Разшири `ImageOptimizer` в генератор на деривати: `{name}-{480,768,1200,1920}.webp` (+ `.avif` ако `function_exists('imageavif')` на сървъра); width/height в `image_variants` таблица или кеш | [app/Support/ImageOptimizer.php](../app/Support/ImageOptimizer.php), нова команда `images:derivatives`; преизползвай backup логиката от `CompressStorageImages.php` |
| Hook на upload: `Service`, `BlogPost` вече викат `ImageOptimizer::optimize`; добави `saved` hook на всички gallery/portfolio photo модели, `TeamMember`, `Partner`, `PortfolioCategory` | `app/Models/*Photo.php` |
| Blade компонент `<x-picture src alt width height sizes :eager :schema />` → `<picture>` с avif/webp `<source srcset>` + `<img width height loading="lazy" decoding="async">`; `eager` = `fetchpriority="high"`, без lazy | `resources/views/components/picture.blade.php` (нов) |
| Hero: CSS `background-image` (`weddings.blade.php:36`, `proms.blade.php:33`, home `.hero`) → `<x-picture eager>` + `<link rel="preload" as="image" imagesrcset imagesizes fetchpriority="high">`; поправи счупения preload на `header.webp` (`home.blade.php:11`) | views + `style.css` |
| Lazy за всичко под fold-а (днес 2/56); сватбената галерия рендерира всяка снимка **два пъти** (desktop + mobile carousel, `weddings.blade.php:200-226`) → веднъж или lazy и двете | service views |
| `width`/`height` на всеки `<img>` (CLS); резервирано място за iframes/карти | всички views |
| Кирилски имена на файлове (`Сватба.jpg`, `Бал.jpeg`, `Индивидуална.jpeg`, `Коли.jpeg`, `РДКР.jpeg`, `реклама.jpg`) → ASCII + update на референциите; изтрий неизползваните гиганти (`graduation-hero 2.jpg` 4,4 MB, `ads/*.png` 8,5 MB, `ads/krustene.jpg` 9 MB) след `grep -rn "ads/" resources` | `public/css/img/` |
| Създай/поправи липсващите: `social-share-cover.jpg`, `best-wedding-cover.jpg`, `default-placeholder.jpg` | – |

**C.5.2 CSS/JS — self-host + bundle с вече инсталирания Vite**

| Стъпка | Файлове |
|---|---|
| `npm i bootstrap @popperjs/core aos glightbox @fontsource/montserrat`; махни неизползваните `tailwindcss`/`@tailwindcss/vite` | `package.json`, `vite.config.js` (inputs: `resources/css/app.css`, `resources/js/app.js`, per-page `resources/css/pages/*.css`) |
| `resources/css/app.css`: само използваните Bootstrap SCSS части (reboot, grid, utilities, nav/navbar/dropdown, buttons, modal, accordion, carousel, forms), `aos.css`, `glightbox.css`, Montserrat 300–700 **latin + cyrillic** с `font-display: swap`, после `public/css/style.css` | нов |
| `resources/js/app.js`: `import * as bootstrap from 'bootstrap'; import AOS from 'aos'; import GLightbox from 'glightbox'; window.GLightbox = GLightbox; AOS.init({duration:600, once:true});`; калкулаторите → модули или `defer` | нов |
| Layout: CDN `<link>`-ове (`app.blade.php:61-70,145`) и скриптове (274-275) → `@vite([...])`; махни per-page unpkg/jsdelivr push-ове и preconnect-ите; preload на двата Montserrat woff2 above-the-fold | layout + 11 views |
| FontAwesome → inline SVG sprite: имената на икони са в DB (`portfolio_categories.icon`, `services.icon_class`, `service_extras/packages.icon_class`) → команда `icons:build` сканира views + DB, копира SVG от `@fortawesome/fontawesome-free/svgs/*` в `public/build/icons.svg`; компонент `<x-icon name>`. Бърза междинна победа: `fontawesome-subset` вместо `all.min.css` (~1,2 MB с шрифтове) | `app/Console/Commands/BuildIconSprite.php`, `components/icon.blade.php` |
| Facades: Google Maps iframes на 7 страници → статична карта + click-to-load; YouTube → poster + click-to-inject | partials |
| Деплой: `public/build` е gitignored, cPanel няма Node → махни `/public/build` от `.gitignore` и commit-вай build output (`npm run build` преди release); алтернатива GitHub Action → `deploy` branch | `.gitignore` |

**C.5.3 Сървър / TTFB / кеш**
- `php artisan optimize` на всеки деплой (`.cpanel.yml`); OPcache в cPanel › Select PHP Version › Options.
- Повторни заявки: `SiteSetting` 8–10× на страница, `PortfolioCategory` 2×, `Testimonial` агрегат при всяка заявка, `PageContent` 2× (`PageController.php:114` + `weddings.blade.php:29`, `proms.blade.php:27`) → `Settings` кеш (C.4.4) + `Cache::remember(…, 3600)` за nav категориите (очаквано −10 заявки/страница).
- **Не включвай LiteSpeed full-page cache** както е сега: всяка страница има `@csrf` форми (`quick-lead-modal.blade.php:13`) → кеширан token = 419 за другите посетители. Само с ESI или token през JS.
- Статичният кеш е ОК; добави `immutable` за Vite hash файлове (в `public/.htaccess` горе), `max-age=3600` за sitemap/llms.
- Компресия: `curl -sI -H 'Accept-Encoding: br, gzip' https://taketwostudio1603.com/css/style.css | grep -i content-encoding`.
- HTML 120 KB на `/weddings`: inline `style=""`/`onmouseover` във footer-а (`app.blade.php:250-254`), дублирана галерия → очаквано ~40 % по-малко.

### C.6 Допълнителни технически находки (извън първоначалния списък)

1. `config/app.php:68` `timezone => 'UTC'` при бизнес в България → влияе на `openingHours`, границите на booking слотовете (`BookingController::WORK_START/END`) и `lastmod`; → `Europe/Sofia` след проверка на booking кода.
2. Graduation мъртъв код след 301-а: `PageController::graduation()` (71-93), `graduation.blade.php`, `public/css/graduation.css`, `public/js/calculators/graduation.js`, `GraduationFaq/GraduationPackage` + Filament ресурси; **`LLMController::full` (72, 82) още публикува graduation пакети/FAQ в `llms-full.txt`** → рекламира redirect-ващ URL. Прехвърли „пред-бална фотосесия“ в `/proms` или махни.
3. Тестове: само Laravel `ExampleTest` skeleton-и; нищо не покрива redirect-и, sitemap, schema.
4. `legal.blade.php:5` слага `index, follow`, но правните страници липсват от sitemap-а → решение в една посока (планът ги включва).
5. Blog pagination (`?page=N`) канонизира към непагинирания URL → страница 2+ да е self-canonical.
6. Единствената дефиниция на днешното production URL поведение е untracked, ръчно редактиран файл на сървъра — никой не може да го review-ва или rollback-ва. Планът го решава с commit.
7. `PageController` с hardcoded метод на услуга; `PortfolioCategory` slug без route → мъртъв линк в навигацията (виж B.4).

---

## Част D — SEO + GEO стратегия (Варна, български)

### D.0 Какво правят конкурентите (проверено на 2026-09-05)

| Страница | Структура | Думи | Цени на страницата | FAQ | Отзиви | Локации по име | Видео |
|---|---|---|---|---|---|---|---|
| vasilevphoto.com/svatben-fotograf-varna/ | H1 = „Сватбен фотограф Варна“, галерии | ~400 | Не (отделна `/ceni/`) | Не | Не | Не | Не |
| vasilevphoto.com/abiturienski-fotosesii-varna/ | keyword slug + city | ~350 | Не | Не | Не | Не | Не |
| studio.chudennsvyat.com (абитуриенти) | списък услуги | ~1200 | **Да** (лв + €; 700 лв цял клас) | Не | Не | Не | Не |
| blagovestafilipova.com | About/Blog/Testimonials/FAQ страници | ~3500 | Не | **Да** | 4 | Не | линк |
| emilhristov.com | отделни ценови страници по услуга | – | Да (подстраници) | Не | Не | Не | Да |
| krasyivanov.com/prom-photography | „Фотограф на абитуриентски бал – Варна“ | ~800 | Не (`/ceni`) | Не | линк към Google | Не | опц. |
| rrusev.com/bal/ (**не е от Варна**, но излиза за „бал Варна цена 2027“) | **H1 „Абитуриентски балове 2027“**, € цени | ~1200 | Да | Не | Не | Не | Да, дрон |

Изводи: (1) **никой конкурент във Варна не комбинира цени + FAQ + отзиви + видео + имена на локации на една страница** — това е свободната ниша; (2) за ценови заявки излизат тези, които показват числа — Take Two вече има € цени, но не в първия екран и не във „форма на отговор“; (3) никой във Варна не притежава „2027“ в заглавие/H1; (4) Bing индексацията на конкурентите не може да се провери отвън — гледа се в Bing Webmaster Tools.

### D.1 Честно позициониране: какво може и какво не може да значи „първи в ChatGPT/Gemini“

- **Не може:** няма „позиция 1“ в LLM отговор. Един и същ въпрос дава различни фирми при различни пускания, потребители и формулировки. Според SOCi Local Visibility Index 2026 ChatGPT препоръчва ~1,2 % от анализираните локации (срещу ~36 % в Google local pack). „Винаги първи, без конкуренция“ не е постижимо и не е проверимо — за никого.
- **Може (и това ще мерим):** „Когато потребител от Варна зададе въпрос за бал/сватба/B2B на български, Take Two Studio 1603 се **споменава в мнозинството пускания и се цитира собственият URL**.“ Цел март 2027: ≥ 50 % за 10-те бал-въпроса, ≥ 30 % сватби, ≥ 30 % B2B (панелът е в D.10). Базовата линия се мери в седмица 1.

Как решава всеки двигател:

| Двигател | Източник за локални BG заявки | Следствие |
|---|---|---|
| ChatGPT (search) | Bing индекс + OAI-SearchBot; ~87 % от цитатите съвпадат с Bing top-10 (Seer Interactive) | Ако страницата не е в Bing → почти никога няма да е цитирана. **Сайтът днес не е в Bing (B13).** |
| Gemini / Google AI Overviews / AI Mode | Google индекс + Google Business Profile/Maps + отзиви | GBP и отзивите доминират |
| Perplexity | собствен crawler + web резултати, тежест към директории/отзиви | страници на трети страни, които споменават студиото |
| Copilot | Bing + Bing Places | Bing Places е директен вход |

Шестте лоста по тежест и къде е сайтът:

| # | Лост | Състояние | Празнина |
|---|---|---|---|
| 1 | GBP: пълнота, брой/свежест/съдържание на отзивите, снимки, публикации | има профил; 3 отзива на сайта подсказват малко Google отзиви | **Голяма** — няма система за отзиви |
| 2 | Bing индекс + Bing Places | BWT има, но сайтът не се вижда в Bing; няма Bing Places; GPTBot 429 | **Голяма** |
| 3 | Консистентност на entity (едно име/телефон/имейл/адрес навсякъде; `sameAs` към всички профили) | **Счупена**: `/proms` показва `info@taketwostudio1603.com` и втори телефон; `sameAs` само FB+IG; няма `/kontakti`, `/za-nas` | Средна, евтина |
| 4 | Цитирания от трети страни (директории, сватбени портали, партньорски страници на зали, асоциация, местни медии) | вероятно ~0 | **Голяма** |
| 5 | Съдържание във „форма на отговор“ с явни цени, числа, FAQ | цените са, но под hero-то; `/weddings` без FAQ; H1 = слоган; блог пост с „195 лв.“ противоречи на € цените | Средна |
| 6 | Свежест (видими дати, година в title, реален `lastmod`, нови отзиви) | sitemap лъже `lastmod`; никъде няма година | Средна |

**Какво НЕ правим (ниска стойност за студио във Варна):** EN версия/hreflang; „doorway“ страници за Добрич/Шумен без реална работа там; купени линкове; Wikidata (ще бъде изтрит за notability); платени „AI visibility“ SaaS; разчитане на `llms.txt` (пазим го, не очакваме много); `meta keywords`; `aggregateRating` от собствени testimonials.

### D.2 Карта на заявките (Варна, български) — качествен приоритет, без измислени обеми

**Абитуриентски балове (P1)**

| Кластер | Примерни заявки | Целева страница | Пр. |
|---|---|---|---|
| Head фото | фотограф за абитуриентски бал Варна; фотограф за бал Варна; фото и видео за бал Варна | `/proms` | P1 |
| Head видео | видеозаснемане абитуриентски бал Варна; бален клип; дрон за бала Варна | `/proms` (секция „Видео и дрон“) | P1 |
| Цена | фотограф за бал Варна цени; колко струва фотограф за бал; цена на ученик | `/ceni#abiturienti` + FAQ на `/proms` + блог ценови гид | P1 |
| Сезон/година | абитуриентски бал Варна 2027; фотограф бал 2027 Варна | **нова** `/abiturientski-bal-varna` (година в title/H1, URL без година) | P1 |
| Етапи | заснемане изпращане абитуриенти Варна; канене на класния; предбална фотосесия Варна | съществуващи блог постове + секции на `/proms` | P2 |
| Индивидуална сесия | абитуриентска фотосесия Варна (студио/открито) | `/proms#fotosesia` | P2 |
| Организатор | как да изберем фотограф за бала; договор; какво включва пакетът | блог (има `/blog/kak-da-organizirate-klasa-za-balen-fotograf`) | P2 |
| Регион | фотограф за бал Добрич/Шумен/Балчик | FAQ „Работите ли извън Варна?“ — без отделни страници | P3 |
| Разговорни (AI) | „Кой е добър фотограф за бал във Варна?“, „Колко струва фото и видео за бал за цял клас във Варна?“ | answer capsule на `/proms` + GBP + цитирания | P1 |

**Сватби (P1, най-силна конкуренция)**

| Кластер | Примерни заявки | Целева страница | Пр. |
|---|---|---|---|
| Head фото | сватбен фотограф Варна; сватбена фотография Варна | `/weddings` | P1 |
| Head видео | сватбен видеограф/видеооператор Варна; сватбено видео Варна; сватбен филм 4K | `/weddings` (диференциатор: един екип фото+видео) | P1 |
| Комбинирано | сватбен фотограф и видеограф Варна; фото и видео за сватба от един екип | H1 на `/weddings` | P1 |
| Цена | сватбен фотограф Варна цени; колко струва сватбен видеограф Варна 2027 | `/ceni#svatbi` + FAQ + блог | P1 |
| Локация | сватба Евксиноград/Св. Св. Константин/Златни пясъци/Дворецът Балчик фотограф; сватба на плажа Варна | **нови** case-study страници `/svatbi/{slug}` за реални сватби | P2 |
| Дрон | дрон за сватба Варна | секция + VideoObject | P2 |
| Разговорни | „Кой е най-добрият сватбен фотограф във Варна?“, „Колко струва сватбен видеограф във Варна 2027?“ | capsule + GBP + сватбени портали | P1 |

**Бизнес (P2, evergreen)**

| Кластер | Примерни заявки | Целева страница |
|---|---|---|
| Продуктова | продуктова фотография Варна; заснемане на продукти за онлайн магазин Варна; рекламна фотография Варна | `/commercial` |
| Хотели/имоти | фотограф за хотел Варна; интериорна/архитектурна фотография Варна; дрон заснемане на имот | `/architectural` |
| Автомобили | автомобилна фотография Варна; снимки на коли за продажба Варна | `/automotive` (P3) |
| Събития | заснемане на фирмено събитие Варна; фотограф за конференция Варна; корпоративно видео Варна | `/events` |
| Видео реклама | видео реклама Варна; дрон видео за бизнес Варна | `/commercial` + `/blog/dron-video-zasnemane-varna-biznes` |

**Второстепенни (P3, не харчим бюджет в първите 90 дни):** кръщене → `/baptism`; семеен фотограф → `/family`; портрет → `/portrait`. Прилага се шаблонът (capsule + FAQ), когато P1/P2 са готови.

### D.3 Архитектура (hub-and-spoke)

**Решение за URL имената:** *не* мигрираме `/proms` → `/fotograf-abiturientski-bal-varna` сега. Slug-ът е слаб сигнал; 301-ите струват седмици re-crawl **точно в сезона за резервации на балове**; Bing реиндексира бавно; трябва да се сменят llms.txt/GBP/социални линкове. **Правило:** старите 9 услуги остават с английски slug; **всички нови страници** са с български транслитериран slug (както блогът вече прави). Преразглеждане само след юли 2027, ако GSC покаже нужда.

```
/                                  Home (H1 остава; + 60-думов entity capsule + линкове към /ceni, /za-nas, /kontakti)
├── /proms                         Hub P1 (пренаписване по D.4)
│   ├── /abiturientski-bal-varna   НОВА: „Абитуриентски бал Варна 2027“ сезонен гид (обновява се всеки август)
│   ├── /blog/category/prom-tips   (поправи/скрий поста с „195 лв.“)
│   └── /booking
├── /weddings                      Hub P1: capsule, пакети, FAQ (напълни WeddingFaq), галерии, видео
│   ├── /svatbi/{slug}             НОВА: реални сватби в конкретни зали (макс 1/месец)
│   └── /blog/category/wedding-tips
├── /commercial                    B2B hub: capsule, лога на клиенти, FAQ (CommercialFaq), процес
│   ├── /automotive  /architectural  /events   (capsule + 4 FAQ всяка, по-късно)
│   └── /blog/category/event-tips, behind-the-scenes
├── /baptism  /family  /portrait   P3 (шаблон по-късно)
├── /ceni                          НОВА: една таблица на услуга в €, anchors #abiturienti #svatbi #biznes, „последна актуализация: дата“
├── /za-nas                        НОВА: за нас + екип (3 Person, реални биографии, техника, договор/срокове); замества /#about
├── /kontakti                      НОВА: NAP + карта + часове + GBP линк + резервация; замества /#contact (каноничната entity страница)
├── /blog                          (+ Person автор)
└── /booking
```

Не създаваме: отделни ценови страници по услуга (`/ceni` + FAQ „Колко струва…“ покриват), градски страници, отделен `/ekip` (влиза в `/za-nas`).

**Вътрешни линкове:** всеки hub: capsule → цени → FAQ → 2–3 свързани статии → `/booking` + линк „Цени за всички услуги“; `/ceni` линква обратно към всеки hub; всяка статия завършва с кутия „Свързана услуга“; case study линква към `/weddings`, `/ceni#svatbi` и сайта на залата. **Навигация:** „За нас“ → `/za-nas`, „Контакти“ → `/kontakti`, ново „Цени“ → `/ceni`; footer добавя трите.

### D.4 Шаблон „answer capsule“ (за всяка hub страница)

1. **H1** = услуга + град (+ година при сезонни).
2. **Capsule (60–80 думи, обикновен `<p>` точно под H1, без карусел):** кой (име на студиото) + какво + къде + ценови диапазон в € + 2 диференциатора + срок на доставка. Това е параграфът, който LLM ще цитира.
3. **H2 секции по интент:** какво включва → цени (таблица) → защо ние (числа) → портфолио/видео → процес → FAQ → доказателства → CTA.
4. **Ценова таблица** в € (лв. в скоби само ако задължението за двойно обозначаване още важи — по Закона за въвеждане на еврото периодът е 12 месеца от 2026-01-01, т.е. до 2026-12-31; провери и махни лв. през януари 2027).
5. **FAQ:** 5–8 въпроса, отговор ≤ 60 думи, в `*Faq` таблиците → FAQPage schema автоматично.
6. **Доказателства:** Google рейтинг + брой (на живо), години, брой балове/сватби, имена на зали, 2–3 цитата с първо име.
7. **CTA:** един телефон, `/booking`, кратка форма.

**Пълен пример за `/proms`:**

- Meta title (58): `Фотограф за абитуриентски бал Варна 2027 | Take Two Studio`
- Meta description (~150): `Фото + 4K видео и дрон за целия клас от 100 € на ученик. Канене, изпращане, бал и 2 фотосесии. Варна и региона. Виж пакетите и запази дата за бал 2027.`
- H1: `Фотограф и видео за абитуриентски бал във Варна (випуск 2027)`
- Capsule (~75 думи): „Take Two Studio 1603 е фото и видео студио във Варна, което заснема абитуриентски балове за цели класове: канене на класния, изпращане от училище, балната вечер и две безплатни фотосесии на класа. Цената е фиксирана на ученик – 100 € (пакет „Парти“) или 120 € (пакет „Лукс“ с дрон и флашка), без скрити такси. Един координиран екип от фотограф, видеооператор и дрон пилот; готовите снимки и клип са в онлайн галерия до [X] дни. Работим във Варна, Добрич и региона.“ *(собственикът попълва [X]; не се публикува placeholder)*
- H2: Какво включва заснемането за целия клас (timeline) · Цени за бал 2027 – на ученик (таблица: Парти 100 €, Лукс 120 €, екстри) · Видео и дрон кадри (showreel + VideoObject) · Защо класовете във Варна избират един екип (числа) · Портфолио от балове във Варна (зали в captions) · Как да организирате класа и да запазите дата · Често задавани въпроси · Отзиви от класове и родители · Калкулатор/контакт
- 6 FAQ (≤ 60 думи всеки): „Колко струва фотограф и видео за бал във Варна?“ · „Кога трябва да запазим дата за бал 2027?“ · „Един екип ли сте за снимки и видео?“ · „Кога получаваме снимките и видеото?“ · „Снимате ли извън Варна – Добрич, Шумен, Балчик?“ · „Какво включва безплатната фотосесия на класа?“ (готови отговори — в изхода на стратегическия агент; поставят се в `prom_faqs`)

**`/weddings` (кратко):** Title ≤ 60 `Сватбен фотограф и видео Варна – цени 2027 | Take Two Studio`; H1 `Сватбен фотограф и видеозаснемане във Варна – един екип за снимки и 4K филм`; capsule с „от 890 € до 1 145 €“, дрон, 4K, срок, зали (Евксиноград, Св. Св. Константин и Елена, Златни пясъци, Балчик); 8 FAQ в `wedding_faqs` (цена, колко предварително, двама фотографи?, доставка, извън Варна, договор/депозит, суров материал, дрон разрешения).

**`/commercial` (кратко):** Title `Продуктова и рекламна фотография Варна | Take Two Studio`; H1 `Рекламна, продуктова и бизнес фотография и видео във Варна`; capsule с „от [X] € продуктова сесия / [Y] € половин ден събитие“, клиенти по име (с разрешение); H2: услуги за бизнеса (линкове към spokes) · цени · процес и срокове · клиенти и проекти · техника · FAQ (права за ползване, брой кадри, срок, фактура, дрон) · запитване.

### D.5 Календар на съдържанието — първите 90 дни (2–4 материала/месец; пренаписването на hub брои за материал)

| Месец | # | Материал | Защо сега |
|---|---|---|---|
| **1 (5.09–5.10)** | 1.1 | Пренаписване на `/proms` по D.4 (+ оправяне на NAP: един имейл, един телефон) | класовете избират сега |
| | 1.2 | Нова `/ceni` — H1 „Цени на фото и видео услуги във Варна (2027)“, секции по услуга, „Актуализирано на: дата“ | хваща всички „цени/колко струва“ заявки; дава числа на AI |
| | 1.3 | Нови `/za-nas` + `/kontakti` (една работна единица): история, 3 биографии (Person), техника, договор/срокове; NAP идентичен с GBP, карта, часове | entity котва |
| | 1.4 | Блог: „Колко струва фотограф и видео за абитуриентски бал във Варна през 2027? (реални цени)“ → `/blog/cena-fotograf-abiturientski-bal-varna-2027`; **поправи/скрий** `/blog/speistete-budjet-balno-zasnemane-varna` („195 лв.“) | директна ценова заявка |
| **2 (5.10–5.11)** | 2.1 | Нова `/abiturientski-bal-varna` — „Абитуриентски бал Варна 2027: дати, зали, срокове, как да изберете фотограф“ (само зали, в които екипът е снимал) | никой във Варна не притежава „2027“ |
| | 2.2 | Upgrade на `/weddings` по D.4 (напълни `wedding_faqs`, capsule, зали, VideoObject, доказателства) | сезон за резервации лято 2027 = септ–март |
| | 2.3 | Case study #1 `/svatbi/{slug}` — реална сватба в конкретна зала (300–500 думи, 15–25 снимки, филм, цитат; писмено съгласие) | залата е най-силният локален entity |
| | 2.4 | Блог: „Сватбен фотограф и видеограф във Варна – цени 2027 и какво включват пакетите“ → `/blog/svatben-fotograf-varna-ceni-2027` | ценова заявка сватби |
| **3 (5.11–5.12)** | 3.1 | Upgrade на `/commercial` по D.4 (лога, 3 мини кейса, `commercial_faqs`) | B2B бюджети за следващата година |
| | 3.2 | Блог: „Продуктова фотография за онлайн магазин във Варна: цени, процес, подготовка“ → `/blog/produktova-fotografiya-varna-ceni-proces` | най-конкретна B2B ценова заявка |
| | 3.3 | Case study #2 (сватба или бал във втора зала — Балчик/Златни пясъци) | втори локационен entity |
| | 3.4 (ако има капацитет) | Блог: „Фотограф за хотел и интериор във Варна“ → `/blog/fotograf-hotel-interior-varna` | архитектурна B2B |

Отложено за Q1 2027: capsule + FAQ за `/baptism`, `/family`, `/portrait`, `/events`, `/automotive`; април — обновяване „изпращане 2027“; август — „2028“.

### D.6 Google Business Profile — playbook

- **Категории:** основна `Фотограф`; допълнителни `Сватбен фотограф`, `Фотографско студио`, `Видеопродукция/Услуги за видеозаснемане`, `Фотограф за събития` (+ `Търговски фотограф`, ако съществува).
- **Полета:** име точно `Take Two Studio 1603` (без добавени ключови думи — нарушение); **един** телефон = `telephone` в schema; каноничен имейл; адрес/часове като на сайта; зона на обслужване Варна + Добрич + Шумен + Балчик + Каварна + Бяла; описание 750 знака с услуги, € цени, зали, година на основаване.
- **Линкове с UTM:** сайт `https://taketwostudio1603.com/?utm_source=google&utm_medium=organic&utm_campaign=gbp`; резервация `https://taketwostudio1603.com/booking?utm_source=google&utm_medium=organic&utm_campaign=gbp_booking`.
- **Услуги с цени:** Абитуриентски бал – цял клас (от 100 €/ученик) · Абитуриентска фотосесия · Сватбена фотография (от 890 €) · Сватбено видео 4K (от 890 €) · Дрон · Кръщене · Продуктова · Интериор/хотели · Автомобилна · Събития · Видео реклама. **Продукти:** Пакет Парти (100 €), Пакет Лукс (120 €), Сватбен пакет фото+видео (от 1 780 €) с линк към страницата.
- **Публикации:** 1/седмица, 60–120 думи, снимка, CTA; септ–февр. се редуват бал/сватба, B2B веднъж месечно. **Снимки:** 5–10/седмица в сезон, файлове `abiturientski-bal-varna-hotel-x-2026.jpg`; 1 видео/месец.
- **Q&A (собственикът задава и отговаря 10):** цена на ученик · и видео ли · Добрич/Шумен · срок за снимките · дрон и разрешение · колко фотографа на сватба · само видео за сватба · продуктови снимки за онлайн магазин · фактура · как се запазва дата.
- **Система за отзиви (най-висок ROI в целия план):** тригер = денят на доставка на галерията (балове: до всеки ученик и родител през организатора; сватби: двойката + родители; B2B: контактът) + QR на флашката/галерията. Цел: 8–12 нови отзива/месец в месеците с доставки; 40+ до юни 2027. Скрипт (SMS/Viber/имейл): „Здравейте, [Име]! Благодарим, че избрахте Take Two Studio 1603 за [бала на 12-В / сватбата ви]. Ако сте доволни от снимките и видеото, ще ни помогне много кратък отзив в Google – 2 минути: [линк]. Ако можете, споменете какво снимахме (бал/сватба), къде беше и какво ви хареса най-много. Благодарим! – Симеон, Кристиана и Пресиан“. Последното изречение вкарва ключови думи (бал, Варна, дрон, зала) без диктовка. Никакви стимули. Отговор на всеки отзив до 48 ч. с шаблони (позитивен/негативен — в изхода на агента).

### D.7 Bing + ChatGPT

1. **Bing Webmaster Tools:** импорт от GSC; submit `sitemap.xml`; Site Scan; **Site Explorer** → провери дали `/`, `/proms`, `/weddings`, `/ceni` са индексирани (не `site:`); URL Submission за „Discovered but not crawled“.
2. **IndexNow:** при публикуване/промяна на страница или пост → ping `https://api.indexnow.org/indexnow?url=…&key=…` (ключ-файл в root). В Laravel: listener на `BlogPost` saved (и на бъдещ `PageContent`), или пакет `ymigval/laravel-indexnow`. Google игнорира IndexNow — за него GSC URL Inspection.
3. **Bing Places for Business:** създай чрез импорт на верифицирания GBP; ако България не е поддържана — пропусни.
4. **Достъп на crawler-и:** след host-fix (Част C) провери в логовете, че `OAI-SearchBot`, `GPTBot` и `bingbot` получават 200 на `/proms`.
5. **llms.txt / llms-full.txt:** пази ги в синхрон (без стар `info@` имейл и без лв. цени); реалната им стойност е като поддържан fact sheet за директории.
6. **Месечна проверка в Bing (BG):** `фотограф за абитуриентски бал Варна`, `сватбен фотограф Варна`, `Take Two Studio 1603` → позиция.

### D.8 Цитирания, споменавания, линкове (български екосистем)

| Канал | Действие | Проверено, че съществува | Стойност |
|---|---|---|---|
| mywedding.bg (Сватбени фотографи – Варна) | профил: NAP, € цени, 10 снимки, линк | да | висока (сватби) |
| weddingday.bg (има рейтинги) | профил + 2 двойки да оставят рейтинг | да | висока |
| starofservice.bg (сватбена + рекламна фотография Варна) | профил с цени | да | средна (агрегаторите се цитират от Perplexity/ChatGPT) |
| НАПСФВ – napsfv.com (асоциация на сватбени фотографи и видеооператори) | членство → профилна страница + „член на…“ на `/za-nas` | да | средна–висока |
| svatbenfotograf.org; АПФ | провери качество преди листване | съществуват | ниска–средна |
| varna24.net/biznes-katalog, guide.varna24.bg, firmi.v.bg, vsichkifirmi.com | безплатни листвания с идентичен NAP | да | ниска всяка; полезни за NAP консенсус |
| Партньорски страници на зали/хотели (балове и сватби; Евксиноград, Св. Св. Константин, Дворецът Балчик) | безплатни снимки от събитието срещу „препоръчани партньори“ линк с credit | не по зали | висока (локално entity съвместно споменаване) |
| Училища / комитети за бал | не е линк тактика: 1-страничен PDF за организатора + бонус за препоръка | – | висока за приходи |
| DJ, агенции, сватбени планери във Варна | взаимни „Партньори“ страници (има `Partner` модел) | не | средна |
| Местни медии (moreto.net, Дарик Варна, Черно море, БНР Варна) | само реално новинарски проекти | съществуват | средна, опортюнистична |
| Wikipedia/Wikidata | пропускаме | – | – |
| Ненамерени: svatba.bg, mysvatba.bg; Варна-специфична директория за балове няма | – | – | – |

**YouTube (собствен, недоизползван):** заглавия `Абитуриентски бал 2026 – [Училище/Хотел], Варна | Take Two Studio 1603`, `Сватбата на [имена] – [Локация], Варна | сватбен филм 4K`; описание с услуга + град, линк към `/ceni` и hub-а, телефон, chapters; плейлисти по услуга; embed на hub-а с `VideoObject`. **Социални профили:** идентично име, град, един телефон, линк към `/kontakti` или `/ceni`; Facebook About с точен адрес/часове; всички + Maps URL в `sameAs`.

### D.9 E-E-A-T и доверие

`/za-nas`: реална история, година на основаване, защо „1603“; 3 биографии (Симеон Тодоров – фотограф; Кристиана Гинчева – фотограф/графичен дизайнер; Пресиан Григоров – видеооператор, дрон пилот) с `Person` schema и Instagram `sameAs`; дрон регистрация/сертификат; застраховка ако има; договорите (PDF има) обяснени на човешки език (депозит, срокове, авторски права, отмяна). Блог: `Автор: … , видеооператор` → `/za-nas#…`, `author: Person`. Списък техника на `/za-nas` и `/commercial`. Case studies със зали. Лога на клиенти с разрешение. Числа (години, балове, сватби, клипове, Google рейтинг на живо). Махни непроверимите „най-добрият във Варна“.

### D.10 Измерване + месечен AI-visibility панел

| KPI | Източник | Цел дек. 2026 | Цел март 2027 |
|---|---|---|---|
| GSC импресии/кликове — кластер балове (бал/абитуриент) | GSC | +50 % импресии | +150 % |
| GSC — кластер сватби (сватб*) | GSC | +30 % | +80 % |
| GSC — кластер B2B (продуктов*, рекламн*, хотел, интериор, събит*) | GSC | +20 % | +50 % |
| Ср. позиция: `фотограф за абитуриентски бал варна` / `сватбен фотограф варна` / `фотограф варна цени` | GSC | top 5 / top 10 / top 5 | top 3 / top 5 / top 3 |
| Bing: индексирани страници, импресии | BWT | всички hub-ове индексирани | растеж |
| GBP: обаждания, упътвания, кликове | GBP Insights | +30 % | +100 % |
| Google отзиви: брой / рейтинг | GBP | +20, ≥ 4,8 | 40+ |
| Запитвания/резервации по източник (utm + „откъде ни намерихте“) | Filament | ≥ 70 % атрибутирани | – |
| Живи цитирания от трети страни | ръчен списък | 8 | 15 |
| AI панел: дял споменавания / позиция / цитиран URL | ръчно | балове ≥ 30 % | балове ≥ 50 %, сватби ≥ 30 %, B2B ≥ 30 % |

**20 фиксирани промпта** (месечно в ChatGPT със search, Gemini, Perplexity, Google AI Overviews/AI Mode; чист/инкогнито профил, BG интерфейс, локация Варна; всеки промпт 2 пъти; записва се: споменат Y/N, позиция, цитиран URL, кои конкуренти са назовани):

Балове: 1) Кой е добър фотограф за абитуриентски бал във Варна? 2) Колко струва фотограф и видео за абитуриентски бал във Варна през 2027? 3) Препоръчай студио за фото и видеозаснемане на бал за цял клас във Варна. 4) Търсим фотограф за изпращането на абитуриентите във Варна – кого да изберем? 5) Кой предлага дрон кадри и 4K видео за абитуриентски бал във Варна? 6) Кои са най-добрите фотографи за бал във Варна с цени на ученик? 7) Абитуриентска фотосесия във Варна – кой е добър и колко струва? 8) Колко предварително трябва да запазим фотограф за бала във Варна и кого препоръчваш? 9) Има ли пакет фото + видео за абитуриентски бал във Варна под 150 евро на ученик? 10) Фотограф за абитуриентски бал в Добрич или Варна, който пътува – кой?
Сватби: 11) Кой е най-добрият сватбен фотограф във Варна? 12) Колко струва сватбен видеограф във Варна през 2027? 13) Препоръчай студио във Варна, което прави и снимки, и видео на сватба с един екип. 14) Сватбен фотограф за сватба в Св. Св. Константин и Елена или Евксиноград – кого да наема? 15) Кой прави сватбено видео с дрон в 4K във Варна?
B2B: 16) Кой прави продуктова фотография за онлайн магазин във Варна и какви са цените? 17) Фотограф за заснемане на хотел и интериор във Варна – препоръки? 18) Автомобилна фотография във Варна – кой снима коли за продажба? 19) Кого препоръчваш за видеозаснемане на фирмено събитие или конференция във Варна? 20) Кой предлага рекламно видео с дрон за бизнес във Варна и колко струва?

### D.11 90-дневна пътна карта (≈ 46 ч. усилие на собственика, ~3,5 ч./седмица)

| Седмици | Действия | Часове | Резултат |
|---|---|---|---|
| W1 (5–12.09) | Предаване на техническия план (Част C). Решение за каноничен NAP (един телефон, един имейл). Базови данни: GSC/BWT/GBP експорти; първо пускане на 20-те промпта в 4 двигателя. BWT: импорт от GSC, sitemap, Site Explorer | 4 | базова линия; статус в Bing; NAP решение |
| W2 (12–19.09) | GBP: категории, описание, услуги с € цени, продукти, UTM, 10 Q&A, 20 снимки. Старт на системата за отзиви (скрипт, кратък линк, QR). Поправка на NAP на `/proms`; скрий поста „195 лв.“; синхронизирай llms.txt | 5 | пълен GBP; заявки за отзиви към всички клиенти от лято 2026 |
| W3–4 (19.09–3.10) | Публикувай `/proms` (capsule, 6 FAQ, година, VideoObject, доказателства). Публикувай `/ceni`. Чернови `/za-nas` + `/kontakti`. Блог 1.4. IndexNow. `sameAs` + TikTok/YouTube/Maps; махни `aggregateRating`; разшири `areaServed` | 8 | бал hub-ът е „answer-ready“ преди пика на решенията |
| W5–6 (3–17.10) | Публикувай `/za-nas`, `/kontakti`; nav + footer + breadcrumbs. Публикувай `/abiturientski-bal-varna`. Цитирания партия 1: mywedding.bg, weddingday.bg, starofservice.bg, varna24, firmi.v.bg. Старт седмични GBP публикации | 7 | entity страници; първи 5 цитирания; „2027“ индексирана |
| W7–8 (17–31.10) | `/weddings` upgrade. Case study #1. Bing Places (импорт GBP). YouTube: преименувай/опиши топ 10 видеа; embed showreels | 7 | сватбен hub готов за сезона; Bing Places |
| W9–10 (31.10–14.11) | Блог 2.4. Кандидатура НАПСФВ. Outreach към 3 зали. Панел #2 + KPI таблица | 5 | ценова страница сватби; 2–3 партньорски споменавания |
| W11–12 (14–28.11) | `/commercial` upgrade. Блог 3.2. Проверка на отзивите; отговори на всички | 6 | B2B hub готов; лога |
| W13 (28.11–5.12) | Case study #2. Панел #3, KPI преглед, план Q1 (кръщене/семейни/портрет capsules, април „изпращане 2027“, август „2028“) | 4 | 90-дневен отчет; Q1 план |

**Реалистичен резултат до март 2027:** студиото е надеждно откриваемо по име и по „фотограф за бал Варна“ в Google и Bing; GBP има 40+ отзива с ключови думи за бал/сватба/зала; 10–15 независими страници го цитират с идентичен NAP; бал-промптите го споменават в около половината пускания. „Без конкуренция“ няма да се случи — но **единственото студио във Варна с цени, FAQ, отзиви и зали на една страница** е това, което реално вдига дела на споменаванията.

---

## Част E — Верификация (подреден checklist)

| # | Проверка | Команда / инструмент | Pass |
|---|---|---|---|
| 1 | Pre-flight на сървъра | `git -C ~/public_html status --porcelain`; `cat ~/public_html/.htaccess` запазен | само `?? .htaccess` |
| 2 | `.env` | `grep -E '^APP_(ENV\|DEBUG\|URL)=' ~/public_html/.env` | `production`, `false`, `https://taketwostudio1603.com` |
| 3 | Redirect матрица | loop-ът в C.1 | всички редове; `num_redirects` = 1 за `http://www…/public/weddings/` |
| 4 | Няма `/public/` в HTML, canonical/og чисти | `curl -s …/weddings \| grep -cE '(href\|src\|content)="[^"]*/public/'` | 0 |
| 5 | Uploads и assets | `curl -sI …/storage/<hero>`; `curl -sI …/css/style.css` | 200 |
| 6 | Admin на чист URL (login, Livewire POST, upload preview) | браузър | без 419/405 |
| 7 | Чувствителни пътища | `.env composer.json vendor/autoload.php storage/logs/laravel.log _legacy/ Ardes/ .git/config` | 403 |
| 8 | Dev routes | `curl -sI …/seed-all …/test-email-send …/optimize.php …/storage/Archive.zip …/ardes/nvidia-02.2026/` | 404/403 |
| 9 | Feature тестове | `php artisan test --filter=Canonical` | зелено |
| 10 | Sitemaps | `curl -s …/sitemap.xml \| xmllint --noout -`; всеки `<loc>` → 200 без redirect: `curl -s …/sitemap-pages.xml \| grep -o '<loc>[^<]*' \| sed 's/<loc>//' \| xargs -I{} curl -s -o /dev/null -w "%{http_code} {}\n" {}`; GSC › Sitemaps „Success“ | без XML грешки |
| 11 | robots.txt | `curl -s …/robots.txt`; GSC robots report | без `/clear-cache`, `/force-login`; Sitemap ред |
| 12 | Structured data | точно 1 `ld+json` блок на страница; Rich Results Test + `validator.schema.org` за `/`, `/weddings`, `/proms`, блог пост | 0 грешки; Breadcrumb + Organization + Article |
| 13 | GPTBot | 20-заявков loop (C.3) от 2 мрежи; лог след 7 дни | само 200 |
| 14 | Favicon/manifest | `curl -sI …/favicon.ico` size > 0; `curl -sI …/site.webmanifest` | 200, валиден JSON |
| 15 | Скорост | PSI API + `npx lighthouse` преди/след за `/`, `/weddings`, `/proms`, 1 пост; WebPageTest | LCP < 2,5 s, CLS < 0,1, TBT < 200 ms, 0 third-party CSS |
| 16 | Bing | BWT › Site Explorer: `/`, `/proms`, `/weddings`, `/ceni` индексирани; IndexNow 200 при publish | индексирани до 2–4 седмици |
| 17 | GSC (седмично, 6 седмици) | Pages, Removals, Core Web Vitals, Enhancements (Breadcrumbs) | `/public/`/`www` → „Page with redirect“; 0 нови „Duplicate“ |
| 18 | AI панел | 20-те промпта (D.10) месечно в 4 двигателя | тренд на дела на споменаванията |

---

## Какво НЕ е част от тази сесия

Само план. Никакви промени в кода/сървъра не са правени (освен неволните странични ефекти от GET заявките, описани в C.0). Следваща стъпка по избор на собственика: (1) имплементация на C.1 + C.2 като първи PR; (2) отделни PR-и за C.4/C.5; (3) съдържанието по D.5 с AI чернови за редакция.

---

## Изпълнение — статус към 2026-09-05 (Фаза 1+2 готова в кода, чака деплой)

Всичко по-долу е commit-нато локално на `laratake` (22 commit-а след `7b5754d`), 80 теста зелени (`php artisan test`). **Нищо още не е деплойнато.**

| Commit | Какво | Покрива |
|---|---|---|
| Fix duplicate /public/ URLs | root `.htaccess` (deny + 1-hop 301 + rewrite в `public/`), нов `public/.htaccess`, `URL::forceRootUrl/forceScheme`, пренаписан `NormalizeCanonicalUrl` (по `getBaseUrl()`), canonical = `url()->current()`, тестове за всички варианти | A, C.1, B3 |
| Security hygiene | махнати `/seed-all`, `/test-email-send`, `/clear-cache`, `public/optimize.php`, stubs, `storage.zip`; dev скриптове → `scripts/`; 52+46 „ 2“ дубликати; `.claude/` извън git; нов robots.txt; throttle на API | C.2, B1, B2, B4a, B12 |
| Favicon/manifest/images | реален `favicon.ico`, `site.webmanifest`, `header.webp`, `social-share-cover.jpg`, `best-wedding-cover.jpg`, `default-placeholder.jpg` | B4d |
| Settings (NAP) | `App\Support\Settings` (кеш по ключ), телефони E.164 + display формат, миграция `normalize_site_settings`, всички views/имейли/LLM през Settings | C.4.4, B9, B17, B19 |
| Schema graph | един `@graph`: Organization ↔ LocalBusiness ↔ WebSite ↔ WebPage(+FAQPage) + BreadcrumbList + ImageObject + Service/Offer + VideoObject + Person + BlogPosting; видими breadcrumbs; без `aggregateRating`; нови колони `services.video_title/video_uploaded_at`, `blog_posts.author_team_member_id` (Filament полета) | C.4.1–C.4.3, B8, B10, B16, B18, B21, B22 |
| FAQ консолидация | 5 таблици + hardcoded масив → `faqs(page_slug…)`, `FaqResource` видим в админа, общ partial, graduation мъртъв код изтрит | B5, C.4.2, C.6.2 |
| Sitemap + IndexNow | sitemapindex (pages/blog/images) с реален `lastmod`, image sitemap, Cache-Control; IndexNow ping при публикуване + `php artisan seo:indexnow --all` + key файл `/{key}.txt` | C.4.5, B11, D.7.2 |
| On-page | title ≤ 60, description ≤ 155, H1 „услуга + Варна“, data migration за hero заглавията | B14, B15 |
| .cpanel.yml | optimize:clear → migrate --force → optimize → indexnow при „Deploy HEAD Commit“ | C.1.6 |
| Public HTML hygiene | inline `<style>` → `public/css/components.css`, GA само след съгласие (без таг в `<head>`), без CSRF meta, без `/force-login`, без inline handlers | одит „публични ключове“ |
| **Фаза 1 (2026-09-05)** | | |
| Entity страници | `/ceni` (всички цени в €, OfferCatalog, FAQ), `/za-nas` (екип + Person, AboutPage), `/kontakti` (NAP, ContactPage, форма); nav/footer; `PageText` за редакция от Filament → Page Contents | D.3, D.4 |
| Сезонен гид | `/abiturientski-bal-varna` (година автоматично, `PromSeason`), таблица бюджет на ученик от пакетите, FAQ, breadcrumbs; answer capsule на `/proms` | D.4, D.5 (2.1) |
| Сватбени истории | `/svatbi/{slug}` от WeddingGallery + нови полета (venue, location, description, couple_quote, video_url) във Filament; Place/ImageObject/VideoObject schema; линкове от `/weddings`; sitemap | D.3, D.5 (2.3) |
| Блог | 2 чернови ценови статии (SeoContentSeeder, непубликувани, за преглед); постът „195 лв.“ → евро (миграция) | D.5 (1.4, 2.4) |
| **Фаза 2 (частично, 2026-09-05, без промени в базата)** | | |
| Answer capsules | `/weddings` и `/commercial` с редактируем capsule (PageText), линкове към `/ceni`, истории, калкулатор; лога на клиенти на `/commercial` от Partners | D.4.2, D.4.3 |
| Self-hosted assets | Bootstrap, AOS, GLightbox, Font Awesome (+webfonts), Montserrat (cyrillic/latin subsets) в `public/vendor` и `public/fonts`; без unpkg/jsdelivr/cdnjs/Google Fonts; preload на 2 шрифта | C.5.2 |
| Карта | Google Maps iframe → click-to-load facade на 8 страници | C.5.2 |
| Изображения | `loading="lazy" decoding="async"` на всички изображения под fold-а, вкл. галерийните модали | C.5.1 |
| Заявки | nav/footer категориите кеширани 1 час с инвалидиране при запис | C.5.3 |

**Не е правено (следващи фази):** остатъкът от performance (Vite bundle, `<x-picture>`, WebP деривати — C.5), GPTBot 429 (тикет към хостинга — C.3), timezone `Europe/Sofia` (C.6.1), GBP/Bing Places/цитирания (D.6–D.8 — действия на собственика).

### Runbook за деплой (в този ред)

1. **GitHub → Private.** Репото е публично; не push-вай преди това. После deploy key за cPanel (виж header-а горе).
2. **Production `.env`** (cPanel File Manager → `public_html/.env`):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://taketwostudio1603.com
   FORCE_CANONICAL_URL=true
   INDEXNOW_KEY=<ключът от Bing Webmaster Tools>
   ```
3. **Push** `laratake` → GitHub.
4. **Сървър (SSH или cPanel Terminal).** cPanel „Update from Remote“ ще отказва merge-а, докато в `public_html` има локални разлики. Реалният случай от 2026-09-05: `public/check_video_path.php` беше изтрит на сървъра (самоизтриващ се stub, а commit-ът също го маха) и `public/css/img/header.webp` беше untracked файл, който репото вече доставя. Почисти и дръпни в една сесия, с maintenance mode, защото между pull и migrate страниците с услуги дават 500:
   ```bash
   cd ~/public_html
   git status --porcelain                                     # виж какво е различно
   git checkout -- public/check_video_path.php                # върни stub-а, merge-ът ще го изтрие
   mv public/css/img/header.webp ~/header.webp.server.bak     # untracked файл, репото го доставя
   [ -f .htaccess ] && ! git ls-files --error-unmatch .htaccess >/dev/null 2>&1 && cp .htaccess ~/htaccess.pre-fix.bak && rm .htaccess   # само ако има ръчен untracked root .htaccess
   git status --porcelain                                     # трябва да е празно (или само ?? файлове, които merge-ът не пипа)
   php artisan down --retry=30 \
     && git pull --ff-only origin laratake \
     && php artisan optimize:clear && php artisan migrate --force && php artisan optimize \
     && php artisan up \
     && php artisan seo:indexnow --all
   ```
   Ако `git pull` изведе нови „would be overwritten“ файлове, повтори за всеки: tracked с промяна → `git checkout -- <файл>`; untracked → `mv <файл> ~/<файл>.bak`. Ако pull-ът мине, а някоя artisan команда падне: `php artisan up` веднага, после виж грешката.
5. **Провери** матрицата от Част C.1 (curl) + `curl -sI https://taketwostudio1603.com/docs/SEO-PLAN.md` → 403.
6. **Изтрий на сървъра** `storage/app/public/Archive.zip` (97 MB) — не е в git.
7. **Filament:** Настройки → попълни `site_youtube` и `site_google_maps`; Услуги → `video_title`/`video_uploaded_at` за showreel-ите; Блог → автор (член на екипа) на постовете; Често задавани въпроси → добави FAQ за family/portrait/automotive/architectural/events.
8. **GSC:** URL Inspection → Request indexing за услугите; Removals за `https://taketwostudio1603.com/public/`; resubmit `sitemap.xml`. **BWT:** import от GSC, submit sitemap, провери IndexNow „Submitted URLs“.
9. **Rollback** при проблем: `cd ~/public_html && cp ~/htaccess.pre-fix.bak .htaccess && git checkout HEAD~1 -- public/.htaccess && php artisan optimize:clear`.

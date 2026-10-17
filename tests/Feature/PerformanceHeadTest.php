<?php

namespace Tests\Feature;

use App\Models\PortfolioCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PageSpeed (mobile) guarantees of layouts/app.blade.php (docs/PAGESPEED-PLAN.md, phases 1, 2.4, 5, K1/K2):
 * inlined critical CSS + non-blocking vendor CSS in the original cascade order, Montserrat as two
 * preloaded variable-font files, accessible nav/landmarks and logo dimensions with the real 787x389 ratio.
 */
class PerformanceHeadTest extends TestCase
{
    use RefreshDatabase;

    private const ASYNC_CSS = ['vendor/bootstrap/bootstrap.min.css', 'vendor/fontawesome/css/all.min.css', 'vendor/aos/aos.css'];

    public static function pages(): array
    {
        return [['/'], ['/weddings'], ['/proms'], ['/booking'], ['/blog'], ['/ceni'], ['/za-nas'], ['/kontakti']];
    }

    #[DataProvider('pages')]
    public function test_head_inlines_critical_css_and_loads_vendor_css_without_blocking(string $path): void
    {
        $this->seed();

        $html = $this->get($path)->assertOk()->getContent();
        $head = substr($html, 0, strpos($html, '</head>'));

        $this->assertSame(1, substr_count($html, '<style id="critical-css">'), "$path: exactly one inlined critical CSS block");
        $this->assertSame(1, substr_count($html, '<style'), "$path: no other inline <style>");
        $critical = $this->between($head, '<style id="critical-css">', '</style>');
        $this->assertLessThan(25 * 1024, strlen($critical), 'critical CSS grew too much: check scripts/critical-css.mjs');

        $styleCss = strpos($head, \App\Support\Assets::versioned('css/style.css'));
        $this->assertNotFalse($styleCss, "$path: style.css stays a normal render-blocking stylesheet");
        $this->assertLessThan($styleCss, strpos($head, '<style id="critical-css">'), 'critical CSS must come before style.css');
        $this->assertStringContainsString('<link rel="stylesheet" href="'.\App\Support\Assets::versioned('css/components.css').'">', $head);

        $previous = strpos($head, '<style id="critical-css">');
        foreach (self::ASYNC_CSS as $file) {
            $tag = '<link rel="stylesheet" href="'.\App\Support\Assets::versioned($file).'" media="print" onload="this.media=\'all\'">';
            $position = strpos($head, $tag);
            $this->assertNotFalse($position, "$path: $file is not loaded with the media=print/onload pattern");
            $this->assertGreaterThan($previous, $position, "$file is out of its original cascade order");
            $this->assertLessThan($styleCss, $position, "$file must stay before style.css (cascade order)");
            $previous = $position;
        }

        $noscript = $this->between($head, '<noscript>', '</noscript>');
        $this->assertStringContainsString(\App\Support\Assets::versioned('vendor/bootstrap/bootstrap.min.css'), $noscript);
        $this->assertStringContainsString(\App\Support\Assets::versioned('vendor/fontawesome/css/all.min.css'), $noscript);
        $this->assertStringNotContainsString('aos.css', $noscript, 'without JS AOS never runs: aos.css would hide every [data-aos] block');

        $this->assertStringNotContainsString('montserrat.css', $head, 'the Montserrat @font-face rules are inlined in the critical CSS');
    }

    public function test_critical_css_keeps_the_hidden_states_and_the_navbar(): void
    {
        $this->seed();

        $critical = $this->between($this->get('/')->getContent(), '<style id="critical-css">', '</style>');

        foreach ([
            '.collapse:not(.show){display:none}',
            '.dropdown-menu.show{display:block}',
            '.fade:not(.show){opacity:0}',
            '.d-none{display:none!important}',
            '.navbar-expand-lg .navbar-collapse{display:flex!important;flex-basis:auto}',
            '*,::after,::before{box-sizing:border-box}',
        ] as $rule) {
            $this->assertStringContainsString($rule, $critical);
        }
        foreach (['.modal{', '.dropdown-menu{', '.navbar-toggler-icon{', '.navbar-dark{', '.container{', '.row{', '.visually-hidden'] as $selector) {
            $this->assertStringContainsString($selector, $critical);
        }
        $this->assertMatchesRegularExpression('#\.modal\{[^}]*display:none#', $critical);
        $this->assertMatchesRegularExpression('#\.dropdown-menu\{[^}]*display:none#', $critical);
    }

    public function test_critical_css_matches_the_vendor_css_it_was_generated_from(): void
    {
        $partial = file_get_contents(resource_path('views/partials/critical-css.blade.php'));

        foreach (['vendor/bootstrap/bootstrap.min.css', 'vendor/fontawesome/css/all.min.css', 'fonts/montserrat/montserrat.css'] as $file) {
            $this->assertStringContainsString(
                $file.' sha1:'.sha1_file(public_path($file)),
                $partial,
                "$file changed since the critical CSS was generated: run node scripts/critical-css.mjs"
            );
        }
    }

    public function test_montserrat_is_two_preloaded_variable_font_files(): void
    {
        $this->seed();

        $html = $this->get('/')->getContent();
        $critical = $this->between($html, '<style id="critical-css">', '</style>');

        preg_match_all('#<link rel="preload" href="([^"]+\.woff2)" as="font" type="font/woff2" crossorigin>#', $html, $preloads);
        $this->assertSame([
            asset('fonts/montserrat/montserrat-400-cyrillic.woff2'),
            asset('fonts/montserrat/montserrat-400-latin.woff2'),
        ], $preloads[1]);

        // Every weight file of a subset is the same variable font, so one @font-face per subset covers 300-700.
        foreach (['cyrillic', 'latin'] as $subset) {
            $this->assertFileEquals(public_path("fonts/montserrat/montserrat-400-$subset.woff2"), public_path("fonts/montserrat/montserrat-700-$subset.woff2"));
            $this->assertMatchesRegularExpression("#@font-face\{[^}]*font-weight:300 700;font-display:swap;src:url\(/fonts/montserrat/montserrat-400-$subset\.woff2\)#", $critical);
        }
        preg_match_all('#url\((/fonts/[^)]+)\)#', $critical, $fonts);
        foreach ($fonts[1] as $font) {
            $this->assertFileExists(public_path(ltrim($font, '/')));
        }
    }

    public function test_fontawesome_does_not_hide_icons_while_its_fonts_load(): void
    {
        $css = file_get_contents(public_path('vendor/fontawesome/css/all.min.css'));

        $this->assertStringNotContainsString('font-display:block', $css);
        $this->assertSame(10, substr_count($css, 'font-display:swap'));
    }

    #[DataProvider('pages')]
    public function test_nav_and_landmarks_are_accessible(string $path): void
    {
        $this->seed();

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<main'), "$path: exactly one <main> landmark");
        $this->assertStringContainsString('<main id="main-content">', $html);
        $this->assertMatchesRegularExpression(
            '#<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="\#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Отвори навигационното меню">#',
            $html
        );
        // Logo file is 787x389.
        $this->assertMatchesRegularExpression('#<img [^>]*class="logo-mobile" width="81" height="40">#', $html);
        $this->assertMatchesRegularExpression('#<img [^>]*class="logo-desktop" width="111" height="55">#', $html);

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//nav//ul | //nav//ol | //footer//ul | //footer//ol') as $list) {
            foreach ($list->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $this->assertSame('li', $child->tagName, "$path: <{$list->tagName}> in the nav/footer contains a <{$child->tagName}>");
                }
            }
        }
    }

    public function test_menu_category_names_are_escaped(): void
    {
        $this->seed();
        PortfolioCategory::create(['slug' => 'escape-test', 'name_bg' => '<b>Тест</b>', 'display_order' => 99, 'is_visible' => true]);
        cache()->forget('nav.portfolio_categories');

        $html = $this->get('/kontakti')->assertOk()->getContent();

        $this->assertStringNotContainsString('<b>Тест</b>', $html);
        $this->assertSame(2, substr_count($html, '&lt;b&gt;Тест&lt;/b&gt;'), 'escaped in the services dropdown and in the footer');
    }

    public function test_layout_assets_are_versioned_and_aos_starts_after_the_first_paint(): void
    {
        $this->seed();

        $html = $this->get('/')->getContent();

        foreach ([...self::ASYNC_CSS, 'css/style.css', 'css/components.css', 'vendor/bootstrap/bootstrap.bundle.min.js', 'vendor/aos/aos.js'] as $file) {
            $this->assertStringContainsString(\App\Support\Assets::versioned($file), $html, "$file must carry ?v= (cached as immutable for a year)");
            $this->assertStringNotContainsString('"'.asset($file).'"', $html, "$file is also referenced without ?v=");
        }

        $this->assertStringContainsString('requestIdleCallback(function () { requestAnimationFrame(initAos); }, { timeout: 1000 });', $html);
        $this->assertStringContainsString('disableMutationObserver: true', $html);
    }

    private function between(string $haystack, string $start, string $end): string
    {
        $from = strpos($haystack, $start);
        $this->assertNotFalse($from, "missing $start");
        $from += strlen($start);

        return substr($haystack, $from, strpos($haystack, $end, $from) - $from);
    }
}

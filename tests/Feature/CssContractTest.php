<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PageSpeed (mobile) guarantees that live in public/css (docs/PAGESPEED-PLAN.md, phases 1.2 and 2.2):
 * the home hero photo is an <img> sized by CSS (not a CSS background the browser discovers late),
 * the team modal photo is sized by a class, and the text colours fixed for WCAG AA contrast
 * (Lighthouse accessibility) do not slide back to gold/grey that fails on their background.
 * Phase 4 (CLS): photos carry no width/height attributes (owner's decision, see PictureComponentTest),
 * so the images that used to collapse while loading reserve their box with CSS `aspect-ratio: auto W / H`.
 */
class CssContractTest extends TestCase
{
    public function test_home_hero_photo_is_an_img_layer_not_a_css_background(): void
    {
        $hero = $this->declarations('style.css', '.hero');
        $this->assertArrayNotHasKey('background-image', $hero, '.hero must not download the LCP photo as a CSS background (it is <img class="hero-bg-img">)');
        $this->assertSame('hidden', $hero['overflow'] ?? null);
        $this->assertSame('75vh', $hero['min-height'] ?? null, 'mobile hero height must stay as before');

        $img = $this->declarations('style.css', '.hero-bg-img');
        foreach (['position' => 'absolute', 'width' => '100%', 'height' => '100%', 'object-fit' => 'cover', 'z-index' => '0'] as $prop => $value) {
            $this->assertSame($value, $img[$prop] ?? null, ".hero-bg-img $prop");
        }

        $this->assertSame('1', $this->declarations('style.css', '.hero .hero-overlay')['z-index'] ?? null);
        $this->assertSame('2', $this->declarations('style.css', '.hero-content')['z-index'] ?? null, 'hero text must stay above the overlay');
        // A global z-index on .hero-overlay would cover the position:relative title of /svatbi/{slug}.
        $this->assertArrayNotHasKey('z-index', $this->declarations('style.css', '.hero-overlay'));

        $this->assertFileExists(public_path('css/img/header.webp'));
    }

    public function test_team_modal_photo_is_sized_by_css(): void
    {
        $rule = $this->declarations('style.css', '.team-img.team-modal-img');
        $this->assertSame('120px', $rule['width'] ?? null);
        $this->assertSame('120px', $rule['height'] ?? null);
        $this->assertSame('cover', $rule['object-fit'] ?? null);
    }

    /** @return array<string, array{string, string, string, ?string}> file, selector, aspect-ratio, the single photo it is reserved for (its real size must match) */
    public static function reservedImageBoxes(): array
    {
        return [
            'about photo (home, /za-nas)' => ['style.css', '.about-img-main', 'auto 1080 / 904', 'css/img/about.webp'],
            'proms intro photo' => ['proms.css', '.intro-photo', 'auto 1600 / 1066', 'css/img/prom.jpg'],
            'weddings intro photo' => ['weddings.css', '.intro-photo', 'auto 1600 / 1067', 'css/img/Сватба.jpg'],
            'game reveal logo' => ['igra.css', '.rv-logo', 'auto 787 / 389', 'css/img/logo-tts-white.webp'],
            'promo banner (800x500 recommended in Filament)' => ['components.css', '.promo-popup-image', 'auto 16 / 10', null],
            'proms/portrait masonry photos' => ['proms.css', '.masonry .portfolio-img', 'auto 2 / 3', null],
            'home partner logos' => ['style.css', '.brand-logo', 'auto 3 / 2', null],
            'commercial client logos' => ['components.css', '.client-logo', 'auto 3 / 2', null],
        ];
    }

    /**
     * "auto W / H" reserves W:H while the image loads and then lets the file's own ratio win,
     * so nothing is cropped or stretched (unlike a height attribute next to a CSS width).
     */
    #[DataProvider('reservedImageBoxes')]
    public function test_images_reserve_their_box_with_css_aspect_ratio(string $file, string $selector, string $ratio, ?string $photo): void
    {
        $this->assertSame($ratio, $this->declarations($file, $selector)['aspect-ratio'] ?? null, "$file: $selector aspect-ratio");

        if ($photo !== null) {
            $size = getimagesize(public_path($photo));
            $this->assertNotFalse($size, "$photo is not readable");
            $this->assertSame("auto {$size[0]} / {$size[1]}", $ratio, "$photo is now {$size[0]}x{$size[1]}: update the reserved ratio in $file");
        }
    }

    public function test_intro_photos_and_commercial_tiles_keep_their_box_while_loading(): void
    {
        foreach (['proms.blade.php', 'weddings.blade.php'] as $view) {
            $this->assertMatchesRegularExpression('#<x-picture[^>]*class="[^"]*\bintro-photo\b#', file_get_contents(resource_path("views/$view")), "$view: intro photo lost its .intro-photo class");
        }

        // max-width: fit-content made every /commercial tile 0x0 until its photo arrived.
        $this->assertNotSame('fit-content', $this->declarations('commercial.css', '.portfolio-item')['max-width'] ?? null);
        $this->assertSame('3/2', $this->declarations('commercial.css', '.portfolio-item')['aspect-ratio'] ?? null);
    }

    /** @return array<string, array{string, string, string, string, float}> file, selector, property holding the background or a literal background, minimum ratio */
    public static function contrastPairs(): array
    {
        return [
            'team role on white' => ['style.css', '.role-text', 'color', '#ffffff', 4.5],
            'process icon on white' => ['style.css', '.process-icon-wrap', 'color', '#ffffff', 3.0],
            'testimonial stars on white card' => ['style.css', ".testimonial-stars.text-warning,\n.testimonial-stars .text-warning", 'color', '#ffffff', 3.0],
            'light-page outline button' => ['style.css', '.btn-custom:not(:where(.hero *))', 'color', '#f8f9fa', 4.5],
            'cookie accept button' => ['components.css', '.btn-cookie-primary', 'color', 'background', 4.5],
            'promo alert on white' => ['components.css', '.badge-gold', 'color', '#fdf0db', 4.5],
            'legal links' => ['components.css', '.legal-content a', 'color', '#ffffff', 4.5],
            'calculator promo price' => ['components.css', '.calc-card label .text-warning', 'color', '#fffcf5', 4.5],
            'weddings service headings' => ['weddings.css', '.text-primary', 'color', '#ffffff', 4.5],
            'weddings extra price' => ['weddings.css', '.extra-price', 'color', '#ffffff', 4.5],
            'weddings extra description' => ['weddings.css', '.extra-description', 'color', '#ffffff', 4.5],
            'weddings submit button' => ['weddings.css', '.btn-custom', 'color', '#D4AF37', 4.5],
            'proms service list' => ['proms.css', '.service-list b', 'color', '#ffffff', 4.5],
            'proms extra price' => ['proms.css', '.extra-price', 'color', '#ffffff', 4.5],
            'proms submit button' => ['proms.css', '.btn-custom', 'color', '#D4AF37', 4.5],
            'baptism extra price' => ['baptism.css', '.extra-price', 'color', '#ffffff', 4.5],
            'baptism submit button' => ['baptism.css', '.btn-custom', 'color', '#D4AF37', 4.5],
            'commercial active filter' => ['commercial.css', ".filter-btn:hover,\n.filter-btn.active", 'color', '#D4AF37', 4.5],
            'commercial promo label on white' => ['commercial.css', '.contact-box .promo-input-label', 'color', '#ffffff', 4.5],
            'booking weekdays' => ['booking.css', '.cal-weekdays', 'color', '#ffffff', 4.5],
            'booking selected day' => ['booking.css', '.cal-day.selected', 'color', '#D4AF37', 4.5],
            'booking submit button' => ['booking.css', '.booking-form-wrapper .btn-custom-full', 'color', '#D4AF37', 4.5],
            'blog card date' => ['blog.css', '.blog-card__date', 'color', '#ffffff', 4.5],
            'blog card more link' => ['blog.css', '.blog-card__more', 'color', '#ffffff', 4.5],
            'blog category badge' => ['blog.css', '.blog-card__category-badge', 'color', 'background', 4.5],
            'blog sidebar cta' => ['blog.css', '.blog-sidebar__cta-btn', 'color', 'background', 4.5],
            'blog post links' => ['blog.css', '.blog-post__body a', 'color', '#ffffff', 4.5],
        ];
    }

    #[DataProvider('contrastPairs')]
    public function test_fixed_colours_keep_wcag_aa_contrast(string $file, string $selector, string $fgProp, string $bg, float $min): void
    {
        $rule = $this->declarations($file, $selector);
        $this->assertArrayHasKey($fgProp, $rule, "$file: $selector has no $fgProp");

        $fg = $this->resolve($rule[$fgProp]);
        $bgColour = str_starts_with($bg, '#') ? $bg : $this->resolve($rule[$bg] ?? '');

        $ratio = $this->contrast($fg, $bgColour);
        $this->assertGreaterThanOrEqual($min, round($ratio, 2), sprintf('%s: %s %s on %s is %.2f:1 (needs %.1f:1)', $file, $selector, $fg, $bgColour, $ratio, $min));
    }

    /** Declarations of every top-level or nested block whose selector is exactly $selector (later blocks win). */
    private function declarations(string $file, string $selector): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path("css/$file")));
        $pattern = '#(?:^|[;{}])\s*'.preg_replace('#\s+#', '\s*', preg_quote($selector, '#')).'\s*\{([^{}]*)\}#';
        $this->assertGreaterThan(0, preg_match_all($pattern, $css, $m), "$file: rule '$selector' not found");

        $out = [];
        foreach ($m[1] as $body) {
            foreach (explode(';', $body) as $decl) {
                if (str_contains($decl, ':')) {
                    [$prop, $value] = array_map('trim', explode(':', $decl, 2));
                    $out[strtolower($prop)] = trim(str_replace('!important', '', $value));
                }
            }
        }

        return $out;
    }

    /** A hex colour, var(--x[, fallback]) from style.css :root, or the colour part of a `background` shorthand. */
    private function resolve(string $value): string
    {
        if (preg_match('#var\(\s*(--[\w-]+)\s*(?:,\s*([^)]+))?\)#', $value, $v)) {
            $root = $this->declarations('style.css', ':root');

            return $this->resolve($root[$v[1]] ?? ($v[2] ?? ''));
        }
        if (preg_match('/#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b/', $value, $hex)) {
            return $hex[0];
        }
        $this->fail("cannot resolve colour '$value'");
    }

    private function contrast(string $a, string $b): float
    {
        [$la, $lb] = [$this->luminance($a), $this->luminance($b)];

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $channels = array_map(function (string $c): float {
            $v = hexdec($c) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}

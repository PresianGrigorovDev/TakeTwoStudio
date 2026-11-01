<?php

namespace App\Support;

use App\Models\ArchitecturalPackage;
use App\Models\AutomotivePackage;
use App\Models\CommercialPackage;
use App\Models\EventPackage;
use App\Models\FamilyPackage;
use App\Models\GraduationPackage;
use App\Models\PortraitPackage;
use App\Models\PromPackage;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\TeamMember;
use App\Support\Seo\PromSeason;

/**
 * {{variables}} that owners type into editable texts (FAQ questions/answers) so prices,
 * delivery times and contacts are read from the database instead of being typed twice:
 *   {{prom.price.3}}   price of prom package #3     {{prom.price.min}}  lowest prom price
 *   {{prom.name.3}}    its name                      {{extra.price.12}}  price of an extra
 *   {{delivery.proms}} delivery days of a service    {{site.phone}}, {{season.year}} ...
 * The admin editor offers them in a dropdown after typing "{{" (js/admin/content-variables.js).
 */
final class ContentVariables
{
    public const PATTERN = '/\{\{\s*([a-z0-9][a-z0-9._-]*)\s*\}\}/i';

    /** Package tables: key prefix => [model, group label]. */
    private const PACKAGE_MODELS = [
        'prom' => [PromPackage::class, 'Абитуриентски балове'],
        'graduation' => [GraduationPackage::class, 'Пред-бални фотосесии'],
        'family' => [FamilyPackage::class, 'Семейни фотосесии'],
        'portrait' => [PortraitPackage::class, 'Портретна фотография'],
        'automotive' => [AutomotivePackage::class, 'Автомобилна фотография'],
        'architectural' => [ArchitecturalPackage::class, 'Архитектурна фотография'],
        'event' => [EventPackage::class, 'Събития'],
        'commercial' => [CommercialPackage::class, 'Рекламна фотография'],
    ];

    /** service_packages (packages edited under Услуги): services.slug => key prefix. */
    private const SERVICE_PACKAGE_PREFIXES = [
        'weddings' => 'wedding',
        'baptism' => 'baptism',
        'commercial' => 'business',
    ];

    /** Container key of the per-request cache (a static would leak between tests). */
    private const CACHE = 'content-variables.all';

    /** @return array<string,array{key:string,label:string,value:string,group:string}> keyed by variable key */
    public static function all(): array
    {
        if (app()->bound(self::CACHE)) {
            return app(self::CACHE);
        }

        $vars = [];
        $add = function (string $key, string $label, $value, string $group) use (&$vars) {
            $vars[$key] = ['key' => $key, 'label' => $label, 'value' => (string) $value, 'group' => $group];
        };

        foreach (self::PACKAGE_MODELS as $prefix => [$model, $title]) {
            $packages = $model::where('is_visible', true)->orderBy('sort_order')->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => trim((string) $p->name), 'price' => (float) $p->price_eur]);
            self::addPackages($add, $prefix, 'Цени: '.$title, $packages);
        }

        $services = Service::whereIn('slug', array_keys(self::SERVICE_PACKAGE_PREFIXES))->with('packages')->get()->keyBy('slug');
        foreach (self::SERVICE_PACKAGE_PREFIXES as $slug => $prefix) {
            if ($service = $services->get($slug)) {
                $packages = $service->packages->sortBy('display_order')
                    ->map(fn ($p) => ['id' => $p->id, 'name' => trim((string) $p->name_bg), 'price' => (float) $p->price_eur]);
                self::addPackages($add, $prefix, 'Цени: '.$service->name_bg, $packages);
            }
        }

        foreach (ServiceExtra::with('service')->orderBy('service_id')->orderBy('display_order')->get() as $extra) {
            if ((float) $extra->price_eur > 0) {
                $add("extra.price.{$extra->id}", ($extra->service?->name_bg ? $extra->service->name_bg.' – ' : '').$extra->label_bg, self::price($extra->price_eur), 'Екстри');
            }
        }

        foreach (Service::where('is_active', true)->whereNotNull('delivery_days')->orderBy('sort_order')->get() as $service) {
            $add("delivery.{$service->slug}", 'Срок за доставка – '.$service->name_bg.' (работни дни)', $service->delivery_days, 'Срокове');
        }

        $add('site.phone', 'Телефон', Settings::phoneDisplay(Settings::phone()), 'Контакти');
        $add('site.email', 'Имейл', Settings::email(), 'Контакти');
        $add('site.address', 'Адрес', Settings::address(), 'Контакти');
        $add('site.hours', 'Работно време', Settings::openingHoursText(), 'Контакти');
        $add('season.year', 'Випуск (година на следващия бал)', PromSeason::year(), 'Сезон');

        if ($since = TeamMember::where('is_active', true)->min('career_start_year')) {
            $add('team.since', 'Снимаме професионално от (година)', $since, 'Екип');
        }

        app()->instance(self::CACHE, $vars);

        return $vars;
    }

    /** Replace every known {{key}}; unknown keys become empty (a deleted package must not leak "{{…}}"). */
    public static function render(?string $text): string
    {
        $text = (string) $text;

        if (! str_contains($text, '{{')) {
            return $text;
        }

        $vars = self::all();

        return preg_replace_callback(self::PATTERN, fn (array $m) => $vars[strtolower($m[1])]['value'] ?? '', $text);
    }

    /** Keys used in $text that do not exist (for form validation). */
    public static function unknownKeys(?string $text): array
    {
        preg_match_all(self::PATTERN, (string) $text, $matches);

        return array_values(array_diff(array_unique(array_map('strtolower', $matches[1])), array_keys(self::all())));
    }

    /** Compact list for the admin dropdown. */
    public static function forEditor(): array
    {
        return array_values(array_map(fn (array $v) => ['k' => $v['key'], 'l' => $v['label'], 'v' => $v['value'], 'g' => $v['group']], self::all()));
    }

    /** Forget the per-request cache (after prices change in the same request, tests). */
    public static function flush(): void
    {
        app()->forgetInstance(self::CACHE);
    }

    /** "130 €", "1 145 €", "99,50 €" – same format as /ceni. */
    public static function price($eur): string
    {
        $eur = (float) $eur;
        $decimals = fmod($eur, 1.0) == 0.0 ? 0 : 2;

        return number_format($eur, $decimals, ',', ' ').' €';
    }

    private static function addPackages(callable $add, string $prefix, string $group, $packages): void
    {
        $packages = collect($packages)->filter(fn ($p) => $p['price'] > 0 && $p['name'] !== '')->values();

        if ($packages->isEmpty()) {
            return;
        }

        $add("{$prefix}.price.min", 'Най-ниска цена', self::price($packages->min('price')), $group);
        $add("{$prefix}.price.max", 'Най-висока цена', self::price($packages->max('price')), $group);

        foreach ($packages as $p) {
            $add("{$prefix}.price.{$p['id']}", $p['name'].' – цена', self::price($p['price']), $group);
            $add("{$prefix}.name.{$p['id']}", $p['name'].' – име', $p['name'], $group);
        }
    }
}

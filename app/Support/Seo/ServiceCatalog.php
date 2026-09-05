<?php

namespace App\Support\Seo;

/**
 * Static SEO facts about the nine service pages that are not (yet) stored in
 * the database: schema.org names/serviceType, breadcrumb labels and the area
 * the studio actually serves. DB values (services.name_bg) win when present.
 */
final class ServiceCatalog
{
    public const SERVICES = [
        'weddings' => ['name' => 'Сватбена фотография и видеозаснемане', 'serviceType' => 'Wedding photography and videography', 'breadcrumb' => 'Сватби'],
        'proms' => ['name' => 'Фотограф и видео за абитуриентски бал', 'serviceType' => 'Prom photography and videography', 'breadcrumb' => 'Абитуриентски балове'],
        'baptism' => ['name' => 'Фотограф и видео за кръщене', 'serviceType' => 'Christening photography and videography', 'breadcrumb' => 'Кръщенета'],
        'commercial' => ['name' => 'Рекламна, продуктова и бизнес фотография', 'serviceType' => 'Commercial and product photography', 'breadcrumb' => 'Рекламна фотография'],
        'family' => ['name' => 'Семейна фотография', 'serviceType' => 'Family photography', 'breadcrumb' => 'Семейни фотосесии'],
        'portrait' => ['name' => 'Портретна фотография', 'serviceType' => 'Portrait photography', 'breadcrumb' => 'Портрети'],
        'automotive' => ['name' => 'Автомобилна фотография', 'serviceType' => 'Automotive photography', 'breadcrumb' => 'Автомобилна фотография'],
        'architectural' => ['name' => 'Архитектурна и интериорна фотография', 'serviceType' => 'Architectural and interior photography', 'breadcrumb' => 'Архитектурна фотография'],
        'events' => ['name' => 'Фото и видео за събития', 'serviceType' => 'Event photography and videography', 'breadcrumb' => 'Събития'],
    ];

    /** Towns/resorts the studio travels to (schema.org areaServed). */
    public const AREA_SERVED = ['Варна', 'Добрич', 'Шумен', 'Балчик', 'Каварна', 'Бяла', 'Златни пясъци', 'Св. Св. Константин и Елена'];

    public const GEO = ['latitude' => 43.21405, 'longitude' => 27.914733];

    public static function has(string $slug): bool
    {
        return isset(self::SERVICES[$slug]);
    }

    public static function get(string $slug, string $field): ?string
    {
        return self::SERVICES[$slug][$field] ?? null;
    }

    /** @return array<int,array<string,string>> */
    public static function areaServed(): array
    {
        $areas = array_map(fn (string $name) => ['@type' => 'City', 'name' => $name, 'containedInPlace' => ['@type' => 'Country', 'name' => 'България']], self::AREA_SERVED);
        $areas[] = ['@type' => 'Country', 'name' => 'България'];

        return $areas;
    }
}

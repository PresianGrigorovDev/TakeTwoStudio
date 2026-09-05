<?php

namespace App\Support\Seo;

use App\Models\Service;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the single schema.org @graph emitted on every page:
 * Organization <- LocalBusiness, WebSite, WebPage (+FAQPage), BreadcrumbList,
 * plus whatever nodes controllers/views added to Seo (Service, VideoObject,
 * Person, BlogPosting ...). Every entity has a stable @id so Google/LLMs can
 * merge the facts across pages instead of seeing a new business each time.
 */
final class JsonLdGraph
{
    public function __construct(private readonly Seo $seo) {}

    public function toArray(string $title, string $description, ?string $image): array
    {
        $root = Seo::root();
        $orgId = Seo::rootId('organization');
        $bizId = Seo::rootId('localbusiness');
        $siteId = Seo::rootId('website');
        $current = url()->current();
        $pageId = $current.'#webpage';
        $crumbId = $current.'#breadcrumb';

        $name = Settings::siteName();
        $social = array_values(Settings::socialLinks());
        $persons = $this->seo->nodesOfType('Person');

        $organization = array_filter([
            '@type' => 'Organization',
            '@id' => $orgId,
            'name' => $name,
            'url' => $root.'/',
            'logo' => ['@type' => 'ImageObject', 'url' => asset('css/img/logo-tts-white.webp'), 'width' => 787, 'height' => 389],
            'email' => Settings::email(),
            'telephone' => Settings::phone(),
            'contactPoint' => $this->contactPoints(),
            'sameAs' => $social ?: null,
            'employee' => $persons ? array_map(fn (array $p) => ['@id' => $p['@id']], $persons) : null,
        ]);

        $business = array_filter([
            '@type' => ['LocalBusiness', 'ProfessionalService'],
            '@id' => $bizId,
            'name' => $name,
            'description' => 'Фото и видео студио във Варна: сватби, абитуриентски балове, кръщенета, семейни и портретни фотосесии, рекламна, автомобилна и архитектурна фотография, заснемане на събития, дрон кадри и 4K видео.',
            'image' => asset('css/img/about.webp'),
            'logo' => asset('css/img/logo-tts-white.webp'),
            'url' => $root.'/',
            'telephone' => Settings::phone(),
            'email' => Settings::email(),
            'priceRange' => '€€',
            'currenciesAccepted' => 'EUR',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => Settings::streetAddress(),
                'addressLocality' => Settings::CITY,
                'postalCode' => Settings::POSTAL_CODE,
                'addressCountry' => 'BG',
            ],
            'geo' => ['@type' => 'GeoCoordinates'] + ServiceCatalog::GEO,
            'areaServed' => ServiceCatalog::areaServed(),
            'openingHoursSpecification' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'opens' => '09:00',
                'closes' => '18:00',
            ],
            'sameAs' => $social ?: null,
            'parentOrganization' => ['@id' => $orgId],
            'knowsAbout' => ['Сватбена фотография', 'Сватбено видео 4K', 'Абитуриентски балове', 'Кръщенета', 'Продуктова фотография', 'Дрон заснемане'],
            'hasOfferCatalog' => $this->offerCatalog(),
        ]);

        $website = [
            '@type' => 'WebSite',
            '@id' => $siteId,
            'url' => $root.'/',
            'name' => $name,
            'inLanguage' => 'bg',
            'publisher' => ['@id' => $orgId],
        ];

        $breadcrumbs = $this->seo->breadcrumbs();
        $faqs = $this->seo->faqs();

        $types = ['WebPage'];
        if ($this->seo->pageType()) {
            $types[] = $this->seo->pageType();
        }
        if ($faqs->isNotEmpty()) {
            $types[] = 'FAQPage';
        }

        $webpage = array_filter([
            '@type' => count($types) === 1 ? 'WebPage' : $types,
            '@id' => $pageId,
            'url' => $current,
            'name' => $title,
            'description' => $description ?: null,
            'isPartOf' => ['@id' => $siteId],
            'about' => ['@id' => $bizId],
            'inLanguage' => 'bg',
            'breadcrumb' => count($breadcrumbs) > 1 ? ['@id' => $crumbId] : null,
            'primaryImageOfPage' => $image ? ['@id' => $current.'#primaryimage'] : null,
            'datePublished' => $this->seo->datePublished()?->toIso8601String(),
            'dateModified' => $this->seo->dateModified()?->toIso8601String(),
            'mainEntity' => $faqs->isNotEmpty() ? $faqs->map(fn (array $f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ])->all() : null,
        ]);

        $graph = [$organization, $business, $website, $webpage];

        if ($image) {
            $graph[] = [
                '@type' => 'ImageObject',
                '@id' => $current.'#primaryimage',
                'url' => $image,
                'contentUrl' => $image,
                'creator' => ['@id' => $orgId],
                'creditText' => $name,
                'copyrightNotice' => '© '.date('Y').' '.$name,
                'license' => route('legal.terms'),
                'acquireLicensePage' => $root.'/#contact',
            ];
        }

        if (count($breadcrumbs) > 1) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $crumbId,
                'itemListElement' => collect($breadcrumbs)->values()->map(fn (array $crumb, int $i) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'] ?? ($i === count($breadcrumbs) - 1 ? $current : null),
                ]))->all(),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => array_merge($graph, $this->seo->nodes())];
    }

    private function contactPoints(): array
    {
        $points = [[
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'telephone' => Settings::phone(),
            'email' => Settings::email(),
            'areaServed' => 'BG',
            'availableLanguage' => ['bg', 'en'],
        ]];

        if ($secondary = Settings::phoneSecondary()) {
            $points[] = array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'name' => Settings::phoneSecondaryLabel(),
                'telephone' => $secondary,
                'areaServed' => 'BG',
                'availableLanguage' => ['bg'],
            ]);
        }

        return $points;
    }

    private function offerCatalog(): ?array
    {
        $services = Cache::remember('seo.active_services', 3600, fn () => Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'name_bg', 'description_bg'])
            ->filter(fn (Service $s) => ServiceCatalog::has($s->slug))
            ->values());

        if ($services->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'OfferCatalog',
            'name' => 'Фотографски и видео услуги',
            'itemListElement' => $services->map(fn (Service $s) => [
                '@type' => 'Offer',
                'itemOffered' => array_filter([
                    '@type' => 'Service',
                    '@id' => url($s->slug).'#service',
                    'name' => $s->name_bg,
                    'url' => url($s->slug),
                ]),
            ])->all(),
        ];
    }
}

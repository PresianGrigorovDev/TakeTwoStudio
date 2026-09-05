<?php

namespace App\Http\Controllers;

use App\Models\ArchitecturalPackage;
use App\Models\AutomotivePackage;
use App\Models\CommercialPackage;
use App\Models\EventPackage;
use App\Models\Faq;
use App\Models\FamilyPackage;
use App\Models\GraduationPackage;
use App\Models\PortraitPackage;
use App\Models\PromPackage;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\PageText;
use App\Support\Seo\Seo;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Standalone entity pages that used to be anchors on the home page:
 * /ceni (all prices), /za-nas (about + team), /kontakti (contact / NAP).
 */
class SitePageController extends Controller
{
    /** @return array<int,array{id:string,title:string,url:string,unit:string,note:string,models:array<int,class-string|array>}> */
    private function priceGroups(): array
    {
        return [
            ['id' => 'abiturienti', 'title' => 'Абитуриентски балове', 'url' => url('/proms'), 'unit' => 'на ученик', 'note' => 'Цената е за целия клас, на ученик: канене на класния, изпращане, балната вечер и фотосесиите на класа.', 'models' => [PromPackage::class]],
            ['id' => 'predbalni', 'title' => 'Пред-бални и абсолвентски фотосесии', 'url' => url('/proms'), 'unit' => '', 'note' => 'Индивидуални и семейни фотосесии в деня на бала или на избрана локация във Варна.', 'models' => [GraduationPackage::class]],
            ['id' => 'svatbi', 'title' => 'Сватби – фотография и видео', 'url' => url('/weddings'), 'unit' => '', 'note' => 'Един екип за снимки и 4K филм. Комбинациите и екстрите се изчисляват в сватбения калкулатор.', 'models' => [['service', 'weddings']]],
            ['id' => 'krashtene', 'title' => 'Кръщене', 'url' => url('/baptism'), 'unit' => '', 'note' => 'Ритуалът в църквата, семейна фотосесия и празненството.', 'models' => [['service', 'baptism']]],
            ['id' => 'semeini', 'title' => 'Семейни фотосесии', 'url' => url('/family'), 'unit' => '', 'note' => '', 'models' => [FamilyPackage::class]],
            ['id' => 'portret', 'title' => 'Портретна фотография', 'url' => url('/portrait'), 'unit' => '', 'note' => '', 'models' => [PortraitPackage::class]],
            ['id' => 'biznes', 'title' => 'Рекламна, продуктова и бизнес фотография', 'url' => url('/commercial'), 'unit' => '', 'note' => 'Продуктова фотография, рекламни видеа, дрон и корпоративно съдържание. За по-големи проекти изготвяме индивидуална оферта.', 'models' => [['service', 'commercial'], CommercialPackage::class]],
            ['id' => 'avtomobilna', 'title' => 'Автомобилна фотография', 'url' => url('/automotive'), 'unit' => '', 'note' => '', 'models' => [AutomotivePackage::class]],
            ['id' => 'arhitekturna', 'title' => 'Архитектурна и интериорна фотография', 'url' => url('/architectural'), 'unit' => '', 'note' => '', 'models' => [ArchitecturalPackage::class]],
            ['id' => 'sabitiya', 'title' => 'Събития', 'url' => url('/events'), 'unit' => '', 'note' => '', 'models' => [EventPackage::class]],
        ];
    }

    public function prices()
    {
        $groups = [];
        $updatedAt = null;

        foreach ($this->priceGroups() as $group) {
            $packages = collect();
            $extras = collect();

            foreach ($group['models'] as $source) {
                if (is_array($source)) {
                    $service = Service::where('slug', $source[1])->with(['packages' => fn ($q) => $q->orderBy('price_eur'), 'extras' => fn ($q) => $q->orderBy('group_name_bg')->orderBy('price_eur')])->first();
                    if ($service) {
                        $packages = $packages->merge($service->packages);
                        $extras = $extras->merge($service->extras);
                    }
                } else {
                    $packages = $packages->merge($source::where('is_visible', true)->orderBy('sort_order')->get());
                }
            }

            $rows = $packages->map(fn ($p) => $this->normalizePackage($p))->filter()->values();

            if ($rows->isEmpty()) {
                continue;
            }

            $groupUpdated = $packages->merge($extras)->max('updated_at');
            $updatedAt = max($updatedAt, $groupUpdated ? Carbon::parse($groupUpdated) : null);

            $groups[] = $group + [
                'packages' => $rows,
                'extras' => $extras->filter(fn ($e) => (float) ($e->price_eur ?? 0) > 0)->map(fn ($e) => [
                    'name' => $e->label_bg ?? $e->name_bg ?? '',
                    'group' => $e->group_name_bg ?? '',
                    'price' => (float) $e->price_eur,
                ])->values(),
            ];
        }

        $faqs = Faq::forPageVisible('ceni');
        $text = PageText::for('ceni');

        $seo = app(Seo::class);
        $seo->setBreadcrumbs([['name' => 'Начало', 'url' => url('/')], ['name' => 'Цени', 'url' => null]]);
        $seo->setFaqs($faqs);
        $seo->addNode([
            '@type' => 'OfferCatalog',
            '@id' => url('/ceni').'#catalog',
            'name' => 'Цени на фото и видео услуги във Варна',
            'url' => url('/ceni'),
            'itemListElement' => collect($groups)->map(fn (array $g) => [
                '@type' => 'OfferCatalog',
                'name' => $g['title'],
                'url' => url('/ceni').'#'.$g['id'],
                'itemListElement' => $g['packages']->map(fn (array $p) => array_filter([
                    '@type' => 'Offer',
                    'name' => $p['name'],
                    'description' => $p['description'] ?: null,
                    'price' => number_format($p['price'], 2, '.', ''),
                    'priceCurrency' => 'EUR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $g['url'],
                ]))->all(),
            ])->all(),
        ]);

        return view('pages.ceni', [
            'groups' => $groups,
            'faqs' => $faqs,
            'text' => $text,
            'updatedAt' => $updatedAt,
        ]);
    }

    public function about()
    {
        $teamMembers = TeamMember::where('is_active', true)->orderBy('display_order')->get();
        $testimonialCount = Testimonial::where('is_active', true)->count();
        $text = PageText::for('za-nas');

        $seo = app(Seo::class)->setPageType('AboutPage');
        $seo->setBreadcrumbs([['name' => 'Начало', 'url' => url('/')], ['name' => 'За нас', 'url' => null]]);
        foreach ($teamMembers as $member) {
            $seo->addNode(PageController::personNode($member));
        }

        return view('pages.za-nas', compact('teamMembers', 'testimonialCount', 'text'));
    }

    public function contact()
    {
        $text = PageText::for('kontakti');

        $seo = app(Seo::class)->setPageType('ContactPage');
        $seo->setBreadcrumbs([['name' => 'Начало', 'url' => url('/')], ['name' => 'Контакти', 'url' => null]]);

        return view('pages.kontakti', [
            'text' => $text,
            'phone' => Settings::phone(),
            'phoneSecondary' => Settings::phoneSecondary(),
            'phoneSecondaryLabel' => Settings::phoneSecondaryLabel(),
            'email' => Settings::email(),
            'address' => Settings::address(),
            'social' => Settings::socialLinks(),
        ]);
    }

    /** Evergreen season guide: /abiturientski-bal-varna ("Абитуриентски бал Варна 2027"). */
    public function promGuide()
    {
        $season = (int) config('seo.prom_season_year', now()->month >= 7 ? now()->year + 1 : now()->year);
        $packages = PromPackage::where('is_visible', true)->orderBy('price_eur')->get();
        $sessions = GraduationPackage::where('is_visible', true)->orderBy('price_eur')->get();
        $faqs = Faq::forPageVisible('abiturientski-bal-varna');
        $text = PageText::for('abiturientski-bal-varna');

        $seo = app(Seo::class);
        $seo->setBreadcrumbs([
            ['name' => 'Начало', 'url' => url('/')],
            ['name' => 'Абитуриентски балове', 'url' => url('/proms')],
            ['name' => "Абитуриентски бал Варна {$season}", 'url' => null],
        ]);
        $seo->setFaqs($faqs);
        $seo->setDates(null, $this->guideUpdatedAt($packages));

        return view('pages.abiturientski-bal-varna', [
            'season' => $season,
            'packages' => $packages,
            'sessions' => $sessions,
            'faqs' => $faqs,
            'text' => $text,
            'minPrice' => $packages->min('price_eur'),
            'maxPrice' => $packages->max('price_eur'),
        ]);
    }

    private function guideUpdatedAt(Collection $packages): ?Carbon
    {
        $dates = collect([
            $packages->max('updated_at'),
            \App\Models\PageContent::where('page_slug', 'abiturientski-bal-varna')->max('updated_at'),
            Faq::forPage('abiturientski-bal-varna')->max('updated_at'),
        ])->filter()->map(fn ($d) => Carbon::parse($d));

        return $dates->max();
    }

    /** @return array{name:string,price:float,description:string,features:array<int,string>,featured:bool}|null */
    private function normalizePackage($package): ?array
    {
        $price = (float) ($package->price_eur ?? 0);
        $name = trim((string) ($package->name_bg ?? $package->name ?? ''));

        if ($price <= 0 || $name === '') {
            return null;
        }

        $features = $package->features ?? [];
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : preg_split('/\r?\n/', $features);
        }

        return [
            'name' => $name,
            'price' => $price,
            'description' => trim(strip_tags((string) ($package->description_bg ?? $package->description ?? ''))),
            'features' => collect($features)->map(fn ($f) => is_array($f) ? implode(' ', array_map('strval', $f)) : (string) $f)->map('trim')->filter()->values()->all(),
            'featured' => (bool) ($package->is_featured ?? $package->is_default ?? false),
        ];
    }
}

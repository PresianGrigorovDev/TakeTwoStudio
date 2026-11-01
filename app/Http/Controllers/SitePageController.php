<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\GraduationPackage;
use App\Models\PromPackage;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\PageText;
use App\Support\PriceList;
use App\Support\Seo\Seo;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Standalone entity pages that used to be anchors on the home page:
 * /ceni (all prices), /za-nas (about + team), /kontakti (contact / NAP).
 */
class SitePageController extends Controller
{
    public function prices()
    {
        ['groups' => $groups, 'updatedAt' => $updatedAt] = PriceList::build();

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
            'email' => Settings::email(),
            'address' => Settings::address(),
            'social' => Settings::socialLinks(),
        ]);
    }

    /** Evergreen season guide: /abiturientski-bal-varna ("Абитуриентски бал Варна 2027"). */
    public function promGuide()
    {
        $season = \App\Support\Seo\PromSeason::year();
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
        $updatedAt = $this->guideUpdatedAt($packages);
        $seo->setDates(null, $updatedAt);

        return view('pages.abiturientski-bal-varna', [
            'season' => $season,
            'updatedAt' => $updatedAt,
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
            Faq::forPageSlug('abiturientski-bal-varna')->max('updated_at'),
        ])->filter()->map(fn ($d) => Carbon::parse($d));

        return $dates->max();
    }
}

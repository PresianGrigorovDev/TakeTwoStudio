<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\WeddingGallery;
use App\Support\PriceList;
use App\Support\Seo\PromSeason;
use App\Support\Seo\ServiceCatalog;
use App\Support\Settings;
use Illuminate\Support\Carbon;

/**
 * /llms.txt and /llms-full.txt: plain-text summaries for AI assistants.
 * Prices come from PriceList (same data as /ceni) and FAQs from the faqs
 * table, so nothing here can drift from the visible site.
 */
class LLMController extends Controller
{
    /** FAQ groups printed in llms-full.txt, in this order. */
    private const FAQ_PAGES = [
        'ceni' => 'Цени',
        'abiturientski-bal-varna' => 'Абитуриентски бал Варна',
        'proms' => 'Абитуриентски балове',
        'weddings' => 'Сватби',
        'baptism' => 'Кръщене',
        'commercial' => 'Рекламна и бизнес фотография',
        'family' => 'Семейни фотосесии',
        'portrait' => 'Портретна фотография',
        'automotive' => 'Автомобилна фотография',
        'architectural' => 'Архитектурна фотография',
        'events' => 'Събития',
    ];

    public function index()
    {
        return $this->plainText(view('llms.index', $this->common() + [
            'stories' => $this->stories(),
            'posts' => $this->latestPosts(15),
        ]));
    }

    public function full()
    {
        $priceList = PriceList::build();

        $faqs = collect(self::FAQ_PAGES)
            ->map(fn (string $title, string $slug) => ['title' => $title, 'items' => Faq::forPageVisible($slug)])
            ->filter(fn (array $group) => $group['items']->isNotEmpty());

        return $this->plainText(view('llms.full', $this->common() + [
            'team' => TeamMember::where('is_active', true)->orderBy('display_order')->get(),
            'testimonials' => Testimonial::where('is_active', true)->latest()->get(),
            'priceGroups' => $priceList['groups'],
            'pricesUpdatedAt' => $priceList['updatedAt'],
            'faqs' => $faqs,
            'stories' => $this->stories(),
            'posts' => $this->latestPosts(30),
        ]));
    }

    /** @return array<string,mixed> */
    private function common(): array
    {
        return [
            'siteName' => Settings::siteName(),
            'tagline' => Settings::tagline(),
            'phone' => Settings::phoneDisplay(Settings::phone()),
            'email' => Settings::email(),
            'address' => Settings::address(),
            'social' => array_filter(Settings::socialLinks()),
            'season' => PromSeason::year(),
            'openingHours' => Settings::openingHoursText(),
            // Max delivery time per service (services.delivery_days, edited in Filament).
            'delivery' => Service::where('is_active', true)->whereNotNull('delivery_days')->orderBy('sort_order')->get(['name_bg', 'slug', 'delivery_days']),
            'areas' => ServiceCatalog::AREA_SERVED,
            'updatedAt' => $this->updatedAt(),
            // Plain-text output: decode entities, drop tags, collapse whitespace.
            'plain' => fn ($value) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
        ];
    }

    private function stories()
    {
        return WeddingGallery::where('is_active', true)->whereNotNull('slug')->orderByDesc('event_date')->get();
    }

    private function latestPosts(int $limit)
    {
        return BlogPost::published()->latest('published_at')->take($limit)->get(['title', 'slug', 'excerpt', 'published_at']);
    }

    private function updatedAt(): ?Carbon
    {
        $dates = collect([
            Faq::max('updated_at'),
            BlogPost::published()->max('published_at'),
            PriceList::build()['updatedAt'],
        ])->filter()->map(fn ($d) => Carbon::parse($d));

        return $dates->max();
    }

    private function plainText($view)
    {
        return response($view->render())
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}

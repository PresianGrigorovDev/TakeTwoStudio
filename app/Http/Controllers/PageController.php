<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Support\Seo\Seo;
use App\Support\Seo\ServiceCatalog;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home()
    {
        $teamMembers = \App\Models\TeamMember::where('is_active', true)->orderBy('display_order')->get();

        $seo = app(Seo::class);
        foreach ($teamMembers as $member) {
            $seo->addNode($this->personNode($member));
        }

        return view('home', [
            'services' => Service::where('is_active', true)->orderBy('sort_order')->get(),
            'teamMembers' => $teamMembers,
            'testimonials' => \App\Models\Testimonial::where('is_active', true)->latest()->get(),
            'partners' => \App\Models\Partner::orderBy('display_order')->get(),
            'portfolioCategories' => \App\Models\PortfolioCategory::orderBy('display_order')->get(),
            'workStart' => BookingController::WORK_START,
            'workEnd' => BookingController::WORK_END,
        ]);
    }

    public function weddings()
    {
        return $this->showService('weddings');
    }

    public function proms()
    {
        return $this->showService('proms');
    }

    public function baptism()
    {
        return $this->showService('baptism'); // Note: route is /baptism singular
    }

    public function commercial()
    {
        return $this->showService('commercial');
    }

    public function family()
    {
        return $this->showService('family');
    }

    public function portrait()
    {
        return $this->showService('portrait');
    }

    public function automotive()
    {
        return $this->showService('automotive');
    }

    public function architectural()
    {
        return $this->showService('architectural');
    }

    public function events()
    {
        return $this->showService('events');
    }

    private function showService($slug)
    {
        $service = Service::where('slug', $slug)->with('activePromotion')->first();

        $portfolioItems = PortfolioItem::whereHas('category', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->latest()->get();

        if ($service) {
            $service->load([
                'packages' => function ($query) {
                    $query->orderBy('price_eur');
                },
                'extras' => function ($query) {
                    $query->orderBy('group_name_bg')->orderBy('price_eur');
                }
            ]);
        }

        $pageContent = \App\Models\PageContent::where('page_slug', $slug)->get()
            ->groupBy('section_slug')
            ->map(function ($section) {
                return $section->pluck('content_bg', 'field_key');
            });

        // Get galleries
        $faqs = Faq::forPageVisible($slug);

        $weddingGalleries = collect();
        if ($slug === 'weddings') {
            $weddingGalleries = \App\Models\WeddingGallery::with('photos')->where('is_active', true)->orderByDesc('event_date')->get();
        }

        $baptismGalleries = collect();
        if ($slug === 'baptism') {
            $baptismGalleries = \App\Models\BaptismGallery::with('photos')->where('is_active', true)->orderByDesc('event_date')->get();
        }

        $promPortfolioPhotos = collect();
        $promPackages = collect();
        if ($slug === 'proms') {
            $promPortfolioPhotos = \App\Models\PromPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
            $promPackages = \App\Models\PromPackage::where('is_visible', true)->orderBy('sort_order')->get();
        }

        $commercialPhotos = collect();
        if ($slug === 'commercial') {
            $commercialPhotos = \App\Models\CommercialPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
        }

        $portraitPortfolioPhotos = collect();
        if ($slug === 'portrait') {
            $portraitPortfolioPhotos = \App\Models\PortraitPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
        }

        $eventPortfolioPhotos = collect();
        if ($slug === 'events') {
            $eventPortfolioPhotos = \App\Models\EventPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
        }

        // New category galleries and packages
        $galleryModelMap = [
            'family' => \App\Models\FamilyGallery::class,
            'automotive' => \App\Models\AutomotiveGallery::class,
            'architectural' => \App\Models\ArchitecturalGallery::class,
        ];

        $packageModelMap = [
            'family' => \App\Models\FamilyPackage::class,
            'portrait' => \App\Models\PortraitPackage::class,
            'automotive' => \App\Models\AutomotivePackage::class,
            'architectural' => \App\Models\ArchitecturalPackage::class,
            'events' => \App\Models\EventPackage::class,
        ];

        $galleries = collect();
        if (isset($galleryModelMap[$slug])) {
            $galleries = $galleryModelMap[$slug]::with('photos')->where('is_active', true)->orderByDesc('event_date')->get();
        }

        $categoryPackages = collect();
        if (isset($packageModelMap[$slug])) {
            $categoryPackages = $packageModelMap[$slug]::where('is_visible', true)->orderBy('sort_order')->get();
        }

        $offerPackages = match (true) {
            $promPackages->isNotEmpty() => $promPackages,
            $categoryPackages->isNotEmpty() => $categoryPackages,
            default => $service?->packages ?? collect(),
        };

        $this->registerServiceSeo($slug, $service, $offerPackages, $faqs);

        return view($slug, [
            'service' => $service,
            'portfolioItems' => $portfolioItems,
            'faqs' => $faqs,
            'weddingGalleries' => $weddingGalleries,
            'baptismGalleries' => $baptismGalleries,
            'promPortfolioPhotos' => $promPortfolioPhotos,
            'promPackages' => $promPackages,
            'commercialPhotos' => $commercialPhotos,
            'portraitPortfolioPhotos' => $portraitPortfolioPhotos,
            'eventPortfolioPhotos' => $eventPortfolioPhotos,
            'galleries' => $galleries,
            'categoryPackages' => $categoryPackages,
            'pageContent' => $pageContent,
        ]);
    }

    /**
     * schema.org Service (+Offer per package, +VideoObject for a YouTube showreel)
     * and the FAQPage entries for the current service page.
     */
    private function registerServiceSeo(string $slug, ?Service $service, $packages, $faqs): void
    {
        $seo = app(Seo::class);
        $url = url($slug);
        $name = $service?->name_bg ?: ServiceCatalog::get($slug, 'name');

        $offers = collect($packages)->map(function ($package) use ($url) {
            $price = $package->price_eur ?? null;
            $title = $package->name_bg ?? $package->name ?? null;

            if ($price === null || (float) $price <= 0 || ! $title) {
                return null;
            }

            return array_filter([
                '@type' => 'Offer',
                'name' => $title,
                'description' => Str::limit(strip_tags((string) ($package->description_bg ?? $package->description ?? '')), 300) ?: null,
                'price' => number_format((float) $price, 2, '.', ''),
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
                'eligibleRegion' => ['@type' => 'Country', 'name' => 'BG'],
                'url' => $url.'#packages',
            ]);
        })->filter()->values()->all();

        $heroImage = ! empty($service?->hero_image) ? asset('storage/'.$service->hero_image) : null;

        $seo->addNode(array_filter([
            '@type' => 'Service',
            '@id' => $url.'#service',
            'name' => $name,
            'serviceType' => ServiceCatalog::get($slug, 'serviceType'),
            'description' => $service?->description_bg ? Str::limit(strip_tags($service->description_bg), 500) : null,
            'url' => $url,
            'image' => $heroImage,
            'provider' => ['@id' => Seo::rootId('localbusiness')],
            'areaServed' => ServiceCatalog::areaServed(),
            'offers' => $offers ?: null,
        ]));

        if ($service?->video_url && preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|shorts/|watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})%i', $service->video_url, $m)) {
            $videoId = $m[1];
            $uploaded = $service->video_uploaded_at ?? $service->updated_at;

            $seo->addNode(array_filter([
                '@type' => 'VideoObject',
                '@id' => $url.'#video',
                'name' => $service->video_title ?: ($name.' – видео'),
                'description' => $service->description_bg ? Str::limit(strip_tags($service->description_bg), 300) : ($name.' от Take Two Studio 1603, Варна.'),
                'thumbnailUrl' => ["https://i.ytimg.com/vi/{$videoId}/maxresdefault.jpg", "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg"],
                'uploadDate' => $uploaded?->toDateString(),
                'embedUrl' => "https://www.youtube.com/embed/{$videoId}",
                'contentUrl' => $service->video_url,
                'publisher' => ['@id' => Seo::rootId('organization')],
            ]));
        }

        if ($faqs->isNotEmpty()) {
            $seo->setFaqs($faqs);
        }
    }

    /** schema.org Person for a team member (E-E-A-T). */
    public static function personNode(\App\Models\TeamMember $member): array
    {
        $image = $member->image_path
            ? (str_starts_with($member->image_path, 'http') ? $member->image_path : asset('storage/'.$member->image_path))
            : null;

        return array_filter([
            '@type' => 'Person',
            '@id' => Seo::rootId('person-'.$member->id),
            'name' => $member->name,
            'jobTitle' => $member->role_bg,
            'description' => $member->bio_bg ? Str::limit(strip_tags($member->bio_bg), 300) : null,
            'image' => $image,
            'worksFor' => ['@id' => Seo::rootId('organization')],
            'sameAs' => $member->instagram_url ? [$member->instagram_url] : null,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\PortfolioItem;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Symfony\Component\VarDumper\VarDumper;

class PageController extends Controller
{
    public function home()
    {
        return view('home', [
            'services' => Service::where('is_active', true)->orderBy('sort_order')->get(),
            'teamMembers' => \App\Models\TeamMember::where('is_active', true)->orderBy('display_order')->get(),
            'testimonials' => \App\Models\Testimonial::where('is_active', true)->latest()->get(),
            'partners' => \App\Models\Partner::orderBy('display_order')->get(),
            'portfolioCategories' => \App\Models\PortfolioCategory::orderBy('display_order')->get(),
            'workStart' => BookingController::WORK_START,
            'workEnd' => BookingController::WORK_END,
            'siteSettings' => \App\Models\SiteSetting::all(),
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

    public function graduation()
    {
        $graduationPhotos = \App\Models\GraduationPortfolioPhoto::where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $graduationFaqs = \App\Models\GraduationFaq::where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $graduationPackages = \App\Models\GraduationPackage::where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        return view('graduation', [
            'graduationPhotos'   => $graduationPhotos,
            'graduationFaqs'     => $graduationFaqs,
            'graduationPackages' => $graduationPackages,
        ]);
    }

    private function showService($slug)
    {
        $service = Service::where('slug', $slug)->first();

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

        $weddingGalleries = collect();
        if ($slug === 'weddings') {
            $weddingGalleries = \App\Models\WeddingGallery::with('photos')->where('is_active', true)->orderByDesc('event_date')->get();
        }

        $baptismGalleries = collect();
        if ($slug === 'baptism') {
            $baptismGalleries = \App\Models\BaptismGallery::with('photos')->where('is_active', true)->orderByDesc('event_date')->get();
        }

        $promPortfolioPhotos = collect();
        $promFaqs = collect();
        if ($slug === 'proms') {
            $promPortfolioPhotos = \App\Models\PromPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
            $promFaqs = \App\Models\PromFaq::where('is_visible', true)->orderBy('sort_order')->get();
        }

        $commercialPhotos = collect();
        if ($slug === 'commercial') {
            $commercialPhotos = \App\Models\CommercialPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
        }

        return view($slug, [
            'service' => $service,
            'portfolioItems' => $portfolioItems,
            'weddingGalleries' => $weddingGalleries,
            'baptismGalleries' => $baptismGalleries,
            'promPortfolioPhotos' => $promPortfolioPhotos,
            'promFaqs' => $promFaqs,
            'commercialPhotos' => $commercialPhotos,
            'pageContent' => $pageContent,
        ]);
    }
}

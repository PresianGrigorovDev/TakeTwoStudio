<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\PortfolioItem;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        return view('home', [
            'services' => Service::where('is_active', true)->get(),
            'teamMembers' => \App\Models\TeamMember::where('is_active', true)->orderBy('display_order')->get(),
            'testimonials' => \App\Models\Testimonial::where('is_active', true)->latest()->get(),
            'partners' => \App\Models\Partner::orderBy('display_order')->get(),
            'portfolioCategories' => \App\Models\PortfolioCategory::orderBy('display_order')->get(),
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

    private function showService($slug)
    {
        $service = Service::where('slug', $slug)->first();

        // If service doesn't exist in DB yet, we might want to fail gracefully or just pass null
        // But for now, we assume seed data exists or will exist.

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
        if ($slug === 'proms') {
            $promPortfolioPhotos = \App\Models\PromPortfolioPhoto::where('is_visible', true)->orderBy('sort_order')->get();
        }

        return view($slug, [
            'service' => $service,
            'portfolioItems' => $portfolioItems,
            'weddingGalleries' => $weddingGalleries,
            'baptismGalleries' => $baptismGalleries,
            'promPortfolioPhotos' => $promPortfolioPhotos,
            'pageContent' => $pageContent,
        ]);
    }
}

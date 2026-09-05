<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use App\Models\TeamMember;
use App\Models\Service;
use App\Models\PromPackage;
use App\Models\GraduationPackage;
use App\Models\FamilyPackage;
use App\Models\PortraitPackage;
use App\Models\AutomotivePackage;
use App\Models\ArchitecturalPackage;
use App\Models\EventPackage;
use App\Models\WeddingFaq;
use App\Models\PromFaq;
use App\Models\GraduationFaq;
use App\Models\BaptismFaq;
use App\Models\CommercialFaq;
use App\Models\Testimonial;

class LLMController extends Controller
{
    public function index()
    {
        $phone = Settings::phoneDisplay(Settings::phone());
        $email = Settings::email();
        $address = Settings::address();
        $tagline = Settings::tagline();
        $siteName = Settings::siteName();

        $social = Settings::socialLinks();
        $instagram = $social['instagram'] ?? 'https://www.instagram.com/taketwostudio1603';
        $facebook = $social['facebook'] ?? 'https://www.facebook.com/taketwostudio1603';
        $tiktok = $social['tiktok'] ?? 'https://www.tiktok.com/@taketwostudio1603';

        return response()->view('llms.index', compact(
            'siteName', 'tagline', 'phone', 'email', 'address', 
            'instagram', 'facebook', 'tiktok'
        ))->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function full()
    {
        $phone = Settings::phoneDisplay(Settings::phone());
        $email = Settings::email();
        $address = Settings::address();
        $tagline = Settings::tagline();
        $siteName = Settings::siteName();

        $social = Settings::socialLinks();
        $instagram = $social['instagram'] ?? 'https://www.instagram.com/taketwostudio1603';
        $facebook = $social['facebook'] ?? 'https://www.facebook.com/taketwostudio1603';
        $tiktok = $social['tiktok'] ?? 'https://www.tiktok.com/@taketwostudio1603';

        // Load team members
        $team = TeamMember::where('is_active', true)->orderBy('display_order')->get();

        // Load testimonials
        $testimonials = Testimonial::where('is_active', true)->latest()->get();

        // Load services and packages dynamically
        $weddingsService = Service::where('slug', 'weddings')->first();
        $weddingsPackages = $weddingsService ? $weddingsService->packages : collect();
        
        $baptismService = Service::where('slug', 'baptism')->first();
        $baptismPackages = $baptismService ? $baptismService->packages : collect();

        $promPackages = PromPackage::where('is_visible', true)->orderBy('sort_order')->get();
        $graduationPackages = GraduationPackage::where('is_visible', true)->orderBy('sort_order')->get();
        $familyPackages = FamilyPackage::where('is_visible', true)->orderBy('sort_order')->get();
        $portraitPackages = PortraitPackage::where('is_visible', true)->orderBy('sort_order')->get();
        $automotivePackages = AutomotivePackage::where('is_visible', true)->orderBy('sort_order')->get();
        $architecturalPackages = ArchitecturalPackage::where('is_visible', true)->orderBy('sort_order')->get();
        $eventPackages = EventPackage::where('is_visible', true)->orderBy('sort_order')->get();

        // Load FAQs dynamically
        $weddingFaqs = WeddingFaq::all();
        $promFaqs = PromFaq::where('is_visible', true)->orderBy('sort_order')->get();
        $graduationFaqs = GraduationFaq::where('is_visible', true)->orderBy('sort_order')->get();
        $baptismFaqs = BaptismFaq::where('is_visible', true)->orderBy('sort_order')->get();
        $commercialFaqs = CommercialFaq::where('is_visible', true)->orderBy('sort_order')->get();

        return response()->view('llms.full', compact(
            'siteName', 'tagline', 'phone', 'email', 'address', 
            'instagram', 'facebook', 'tiktok', 'team', 'testimonials',
            'weddingsPackages', 'baptismPackages', 'promPackages', 'graduationPackages',
            'familyPackages', 'portraitPackages', 'automotivePackages', 'architecturalPackages',
            'eventPackages', 'weddingFaqs', 'promFaqs', 'graduationFaqs', 'baptismFaqs', 'commercialFaqs'
        ))->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}

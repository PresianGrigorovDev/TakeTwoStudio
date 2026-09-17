<?php

namespace App\Providers;

use App\Support\Assets;
use App\Support\Seo\Seo;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Seo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->forceCanonicalUrls();
        $this->registerAdminAssets();
    }

    /**
     * Self-hosted QR generator for the admin panel (Маркетинг → QR стикери):
     * qrcode-generator (MIT) builds the module matrix, public/js/admin/game-qr.js draws
     * the styled SVG/PNG. Registered here (not via Panel::assets()) because Filament
     * registers panel assets in both Panel::register() and Panel::boot(), which loads
     * every script twice.
     */
    private function registerAdminAssets(): void
    {
        FilamentAsset::register([
            Js::make('qrcode-generator', asset('vendor/qrcode/qrcode.min.js')),
            Js::make('game-qr', Assets::versioned('js/admin/game-qr.js')),
        ], package: 'taketwo');
    }

    /**
     * Make url()/asset()/route()/Storage::url() independent of how the request
     * arrived (e.g. via /public/... on a shared host whose docroot is the
     * project root). Only active outside local/testing and only when APP_URL
     * is an https URL, so a misconfigured .env can never break link generation.
     */
    private function forceCanonicalUrls(): void
    {
        if (! config('app.force_canonical') || $this->app->environment('local', 'testing')) {
            return;
        }

        $root = rtrim((string) config('app.url'), '/');

        if (! str_starts_with($root, 'https://')) {
            return;
        }

        URL::forceRootUrl($root);
        URL::forceScheme('https');
    }
}

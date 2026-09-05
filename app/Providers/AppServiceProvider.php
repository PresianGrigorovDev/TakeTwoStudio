<?php

namespace App\Providers;

use App\Support\Seo\Seo;
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

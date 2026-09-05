<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="google-site-verification" content="fmGR5uGwOviE4zl38-ww4bLzdYg-U2ZmSTbybxgHhaU" />
    
    <title>@yield('title', 'Сватбен фотограф и Видеозаснемане Варна | Балове и Кръщенета | Take Two Studio 1603')</title>
    
    <meta name="title" content="@yield('meta_title', 'Сватбен фотограф и Видеозаснемане Варна | Балове и Кръщенета | Take Two Studio 1603')">
    <meta name="description" content="@yield('meta_description', 'Търсите сватбен фотограф във Варна? Take Two Studio 1603 предлага професионално фото и видеозаснемане за сватби, абитуриентски балове и кръщенета в цялата страна. 4K качество и дрон услуги.')">
    <meta name="keywords" content="@yield('meta_keywords', 'сватбен фотограф варна, видеозаснемане сватба, фотограф за бал, заснемане на кръщене, професионална фотосесия варна, дрон услуги, Take Two Studio 1603')">
    <meta name="author" content="Take Two Studio 1603">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">

    @php
        // url()->current() is safe: URL::forceRootUrl() (AppServiceProvider) and the
        // root .htaccess guarantee it never carries a /public or /index.php base path.
        // Paginated listings (?page=2+) are self-canonical so Google indexes each page.
        $canonicalUrl = request()->integer('page') > 1 ? url()->full() : url()->current();
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:title" content="@yield('og_title', 'Сватбен фотограф и Видеозаснемане Варна | Take Two Studio 1603')">
    <meta property="og:description" content="@yield('og_description', 'Запазете вашите спомени с нас! Професионално заснемане на сватби, балове и кръщенета във Варна и цяла България. Вижте портфолиото ни.')">
    <meta property="og:image" content="@yield('og_image', asset('css/img/social-share-cover.jpg'))">
    <meta property="og:locale" content="bg_BG">
    <meta property="og:site_name" content="Take Two Studio 1603">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@@taketwostudio1603">
    <meta name="twitter:title" content="@yield('og_title', 'Сватбен фотограф и Видеозаснемане Варна | Take Two Studio 1603')">
    <meta name="twitter:description" content="@yield('og_description', 'Запазете вашите спомени с нас! Професионално заснемане на сватби, балове и кръщенета във Варна и цяла България.')">
    <meta name="twitter:image" content="@yield('og_image', asset('css/img/social-share-cover.jpg'))">

    <meta name="geo.region" content="BG-03">
    <meta name="geo.placename" content="Varna">
    <meta name="geo.position" content="43.21405;27.914733">
    <meta name="ICBM" content="43.21405, 27.914733">

    <link rel="preload" href="{{ asset('fonts/montserrat/montserrat-400-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/montserrat/montserrat-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @yield('preload')

    {{-- All CSS/JS/fonts are self-hosted: no third-party origins before the visitor consents to anything. --}}
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('fonts/montserrat/montserrat.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/aos/aos.css') }}" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('css/img/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('css/img/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('css/img/favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">

    @include('partials.schema-graph')

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    @stack('styles')
</head>

<body>

    <nav class="navbar navbar-expand-lg fixed-top navbar-dark">
        <div class="container h-100">
            <a class="navbar-brand d-lg-none" href="/">
                <img src="{{ asset('css/img/logo-tts-white.webp') }}" alt="Take Two Studio" class="logo-mobile" width="100" height="40">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-center h-100" id="navbarNav">
                <ul class="navbar-nav align-items-center w-100 justify-content-between h-100">
                    <div class="d-flex flex-column flex-lg-row h-100 align-items-center">
                        <li class="nav-item"><a class="nav-link" href="{{ route('pages.about') }}">За нас</a></li>
                        <li class="nav-item"><a class="nav-link" href="/#portfolio">Портфолио</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('blog.index') }}">Блог</a></li>
                    </div>

                    <li class="nav-item d-none d-lg-flex align-items-center mx-auto">
                        <a class="navbar-brand m-0" href="/">
                            <img src="{{ asset('css/img/logo-tts-white.webp') }}" alt="Take Two Studio" class="logo-desktop" width="138" height="55">
                        </a>
                    </li>

                    <div class="d-flex flex-column flex-lg-row h-100 align-items-center">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="/#services" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Услуги
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                @php
                                $portfolioCategories = \App\Models\PortfolioCategory::visibleCached();
                                    foreach ($portfolioCategories as $portfolioCategory)
                                       if($portfolioCategory->is_visible)
                                            echo '<li><a class="dropdown-item" href="' . url($portfolioCategory->slug) . '"><i class="fas fa-' . $portfolioCategory->icon . '"></i> ' . $portfolioCategory->name_bg . '</a></li>';
                                @endphp
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('pages.prices') }}">Цени</a></li>
                        <li class="nav-item"><a class="nav-link" href="/booking">Резервация</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('pages.contact') }}">Контакти</a></li>
                    </div>
                </ul>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer class="text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h3>Take Two Studio 1603</h3>
                    <p>{{ \App\Support\Settings::tagline() }}</p>
                    <div class="social-links">
                        @foreach (\App\Support\Settings::socialLinks() as $network => $url)
                            @php $icon = ['facebook' => 'fab fa-facebook-f', 'instagram' => 'fab fa-instagram', 'tiktok' => 'fab fa-tiktok', 'youtube' => 'fab fa-youtube', 'google_maps' => 'fas fa-map-marker-alt'][$network]; @endphp
                            <a href="{{ $url }}" class="social-link" target="_blank" rel="noopener" aria-label="{{ ucfirst(str_replace('_', ' ', $network)) }}"><i class="{{ $icon }}"></i></a>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-4">
                    <h3>Нашите Услуги</h3>

                    <ul>
                        @php
                            $portfolioCategories = \App\Models\PortfolioCategory::visibleCached();
                            foreach ($portfolioCategories as $category) {
                                echo '<li><a href="' . url($category->slug) . '">' . $category->name_bg . '</a></li>';
                            }
                        @endphp
                        {{-- <li><a href="{{ url('weddings') }}">Сватбена фотография и видео</a></li>
                        <li><a href="{{ url('proms') }}">Абитуриентски балове и фотосесии</a></li>
                        <li><a href="{{ url('baptism') }}">Детска фотография и Кръщенета</a></li>
                        <li><a href="{{ url('commercial') }}">Рекламна и продуктова фотография</a></li>
                        <li><a href="{{ url('commercial') }}">Корпоративно видеозаснемане и дрон</a></li>
                        <li><a href="{{ url('graduation') }}">Пред-бална фотосесия</a></li> --}}
                    </ul>
                </div>
                <div class="col-md-4">
                    <h3>Контакти</h3>
                    <ul class="contact-list">
                        <li><span>📍</span> {{ \App\Support\Settings::address() }}</li>
                        <li><span>📞</span> <a href="tel:{{ \App\Support\Settings::phoneHref(\App\Support\Settings::phone()) }}">{{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}</a></li>
                        <li><span>✉️</span> <a href="mailto:{{ \App\Support\Settings::email() }}">{{ \App\Support\Settings::email() }}</a></li>
                        <li><span>🕒</span> Понеделник - Неделя</li>
                        <li><span>📝</span> <a href="{{ route('blog.index') }}">Блог</a></li>
                        <li><span>💶</span> <a href="{{ route('pages.prices') }}">Цени</a></li>
                        <li><span>👥</span> <a href="{{ route('pages.about') }}">За нас</a> &bull; <a href="{{ route('pages.contact') }}">Контакти</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row">
                <div class="col-md-12 text-center m-auto">
                    <p class="mb-2">
                        <a href="{{ route('legal.privacy') }}" class="footer-link">Политика за поверителност</a>
                        &bull;
                        <a href="{{ route('legal.terms') }}" class="footer-link">Общи условия</a>
                        &bull;
                        <a href="{{ route('legal.cookies') }}" class="footer-link">Политика за бисквитки</a>
                    </p>
                    <p>
                        © {{ date('Y') }} Take Two Studio 1603. Всички права запазени.
                        @auth
                            @if(auth()->user()->isAdmin())
                                &bull; <a href="{{ url('/admin') }}" class="footer-link">Админ Панел</a>
                            @endif
                        @endauth
                    </p>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.cookie-banner')
    @include('partials.promo-popup')
    @include('partials.mobile-sticky-cta')
    @include('partials.quick-lead-modal')

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('vendor/aos/aos.js') }}" defer></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                if (typeof AOS !== 'undefined') {
                    AOS.init({
                        duration: 1000,
                        once: true
                    });
                }
            }, 100);
        });
    </script>
    @stack('scripts')
</body>
</html>

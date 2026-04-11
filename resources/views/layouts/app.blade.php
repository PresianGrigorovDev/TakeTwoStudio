<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="fmGR5uGwOviE4zl38-ww4bLzdYg-U2ZmSTbybxgHhaU" />
    
    <title>@yield('title', 'Сватбен фотограф и Видеозаснемане Варна | Балове и Кръщенета | Take Two Studio 1603')</title>
    
    <meta name="title" content="@yield('meta_title', 'Сватбен фотограф и Видеозаснемане Варна | Балове и Кръщенета | Take Two Studio 1603')">
    <meta name="description" content="@yield('meta_description', 'Търсите сватбен фотограф във Варна? Take Two Studio 1603 предлага професионално фото и видеозаснемане за сватби, абитуриентски балове и кръщенета в цялата страна. 4K качество и дрон услуги.')">
    <meta name="keywords" content="@yield('meta_keywords', 'сватбен фотограф варна, видеозаснемане сватба, фотограф за бал, заснемане на кръщене, професионална фотосесия варна, дрон услуги, Take Two Studio 1603')">
    <meta name="author" content="Take Two Studio 1603">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">

    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    @yield('preload')
    
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></noscript>

    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></noscript>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('css/img/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('css/img/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('css/img/favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="icon" href="{{ asset('css/img/favicon_io/favicon-16x16.png') }}" sizes="any">

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@type": ["LocalBusiness", "ProfessionalService"],
      "name": "Take Two Studio 1603",
      "image": "{{ asset('css/img/about.webp') }}",
      "@id": "https://taketwostudio1603.com",
      "url": "https://taketwostudio1603.com",
      "telephone": "{{ \App\Models\SiteSetting::find(4)->setting_value }}",
      "priceRange": "€€",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ \App\Models\SiteSetting::find(6)->setting_value }}",
        "addressLocality": "Варна",
        "postalCode": "9000",
        "addressCountry": "BG"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 43.21405,
        "longitude": 27.914733
      },
      "areaServed": {
        "@type": "City",
        "name": "Варна",
        "addressCountry": "BG"
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "{{ \App\Models\SiteSetting::find(7)->setting_value }}:00",
        "closes": "{{ \App\Models\SiteSetting::find(8)->setting_value }}:00"
      },
      "sameAs": [
        "{{ \App\Models\SiteSetting::find(7)->setting_value }}",
        "{{ \App\Models\SiteSetting::find(8)->setting_value }}"
      ],
      "description": "Професионално сватбено фото и видеозаснемане във Варна. Услуги за балове, кръщенета и корпоративни събития.",
      "knowsAbout": ["Сватбена фотография", "Видеозаснемане с дрон", "Абитуриентски балове", "Кръщенета"],
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Фотографски и видео услуги",
        "itemListElement": [
          {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Сватбена фотография и видеозаснемане"}},
          {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Абитуриентски балове и фотосесии"}},
          {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Заснемане на кръщенета"}},
          {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Рекламна и продуктова фотография"}},
          {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Дрон заснемане"}},
          {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Пред-бална фотосесия"}}
        ]
      }
    }
    </script>
    @stack('schema')

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
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
                        <li class="nav-item"><a class="nav-link" href="/#about">За нас</a></li>
                        <li class="nav-item"><a class="nav-link" href="/#portfolio">Портфолио</a></li>
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
                                $portfolioCategories = \App\Models\PortfolioCategory::where('is_visible', true)->get();
                                    foreach ($portfolioCategories as $portfolioCategory)
                                       if($portfolioCategory->is_visible)
                                            echo '<li><a class="dropdown-item" href="' . url($portfolioCategory->slug) . '"><i class="fas fa-' . $portfolioCategory->icon . '"></i> ' . $portfolioCategory->name_bg . '</a></li>';
                                @endphp
                            </ul>
                        </li>
                        {{-- <li class="nav-item"><a class="nav-link" href="/booking">Резервация</a></li> --}}
                        <li class="nav-item"><a class="nav-link" href="/#contact">Контакти</a></li>
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
                    <p>
                        @if (\App\Models\SiteSetting::find(14))
                            {{ \App\Models\SiteSetting::find(14)->setting_value }}
                        @else
                            {{ null }}
                        @endif
                    </p>
                    <div class="social-links">
                        <a href="{{ \App\Models\SiteSetting::find(7)->setting_value }}" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="{{ \App\Models\SiteSetting::find(8)->setting_value }}" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h3>Нашите Услуги</h3>

                    <ul>
                        @php
                            $portfolioCategories = \App\Models\PortfolioCategory::where('is_visible', true)->get();
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
                        <li><span>📍</span> {{ \App\Models\SiteSetting::find(6)->setting_value }}</li>
                        <li><span>📞</span> <a href="tel:{{ \App\Models\SiteSetting::find(4)->setting_value }}">{{ \App\Models\SiteSetting::find(4)->setting_value }}</a></li>
                        <li><span>✉️</span> <a href="mailto:{{ \App\Models\SiteSetting::find(5)->setting_value }}">{{ \App\Models\SiteSetting::find(5)->setting_value }}</a></li>
                        <li><span>🕒</span> Понеделник - Неделя</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row">
                <div class="col-md-12 text-center m-auto">
                    <p class="mb-2">
                        <a href="{{ route('legal.privacy') }}" style="color: #ccc; text-decoration: none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ccc'">Политика за поверителност</a>
                        &bull;
                        <a href="{{ route('legal.terms') }}" style="color: #ccc; text-decoration: none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ccc'">Общи условия</a>
                        &bull;
                        <a href="{{ route('legal.cookies') }}" style="color: #ccc; text-decoration: none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ccc'">Политика за бисквитки</a>
                    </p>
                    <p>
                        © {{ date('Y') }} Take Two Studio 1603. Всички права запазени.
                        @auth
                            @if(auth()->user()->isAdmin())
                                &bull; <a href="{{ url('/admin') }}" style="color: #ccc; text-decoration: none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ccc'">Админ Панел</a>
                            @endif
                        @endauth
                    </p>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.cookie-banner')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
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

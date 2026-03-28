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
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'Сватбен фотограф и Видеозаснемане Варна | Take Two Studio 1603')">
    <meta property="og:description" content="@yield('og_description', 'Запазете вашите спомени с нас! Професионално заснемане на сватби, балове и кръщенета във Варна и цяла България. Вижте портфолиото ни.')">
    <meta property="og:image" content="{{ asset('css/img/social-share-cover.jpg') }}">
    <meta property="og:locale" content="bg_BG">

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
      "@type": "ProfessionalService",
      "name": "Take Two Studio 1603",
      "image": "{{ asset('css/img/about.webp') }}",
      "@id": "https://taketwostudio1603.com",
      "url": "https://taketwostudio1603.com",
      "telephone": "+359886190124",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Варна",
        "addressLocality": "Varna",
        "postalCode": "9000",
        "addressCountry": "BG"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 43.21405,
        "longitude": 27.914733
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "09:00",
        "closes": "20:00"
      },
      "sameAs": [
        "https://www.facebook.com/taketwostudio1603",
        "https://www.instagram.com/taketwostudio1603"
      ],
      "description": "Професионално сватбено фото и видеозаснемане във Варна. Услуги за балове, кръщенета и корпоративни събития.",
      "knowsAbout": ["Сватбена фотография", "Видеозаснемане с дрон", "Абитуриентски балове", "Кръщенета"]
    }
    </script>

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
                        <li class="nav-item"><a class="nav-link" href="/#services">Услуги</a></li>
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
                        Професионална фотография и видеозаснемане във Варна и цялата страна.
                        Ние улавяме емоциите от вашите сватби, кръщенета, абитуриентски балове
                        и корпоративни събития. Превръщаме миговете в изкуство чрез 4K видео,
                        дрон кадри и креативен поглед.
                    </p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/taketwostudio1603" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/taketwostudio1603" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h3>Нашите Услуги</h3>
                    <ul>
                        <li><a href="{{ url('weddings') }}">Сватбена фотография и видео</a></li>
                        <li><a href="{{ url('proms') }}">Абитуриентски балове и фотосесии</a></li>
                        <li><a href="{{ url('baptism') }}">Детска фотография и Кръщенета</a></li>
                        <li><a href="{{ url('commercial') }}">Рекламна и продуктова фотография</a></li>
                        <li><a href="{{ url('commercial') }}">Корпоративно видеозаснемане и дрон</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h3>Контакти</h3>
                    <ul class="contact-list">
                        <li><span>📍</span> ж.к. Възраждане IV 1603, Варна, България</li>
                        <li><span>📞</span> <a href="tel:0886190124">088 619 0124</a></li>
                        <li><span>✉️</span> info@taketwostudio1603.com</li>
                        <li><span>🕒</span> Понеделник - Неделя</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row">
                <div class="col-md-12 text-center m-auto">
                    <p>© {{ date('Y') }} Take Two Studio 1603. Всички права запазени.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.19.0/matter.min.js" defer></script>

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

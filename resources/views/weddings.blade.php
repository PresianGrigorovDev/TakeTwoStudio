@extends('layouts.app')

@section('title', 'Сватбен Фотограф и Видеозаснемане Варна | Take Two Studio 1603')
@section('meta_description', 'Търсите сватбен фотограф и видеозаснемане във Варна? Кинематографични сватбени филми с 4K качество, дрон кадри и авторска фотография. Изчислете цена с калкулатора!')
@section('meta_keywords', 'сватбен фотограф варна, сватбено видеозаснемане варна, сватбен калкулатор варна, сватбен фотограф варна цени, дрон за сватба, Take Two Studio 1603')
@section('og_title', 'Сватбен Фотограф и Видеозаснемане Варна | Take Two Studio 1603')
@section('og_description', 'Професионално заснемане на сватби във Варна и цяла България. 4K видео, дрон и авторска фотография. Вижте нашите пакети!')
@section('og_image', asset('css/img/Сватба.jpg'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/weddings.css') }}">
@endpush

@section('preload')
    @if(!empty($service->hero_image))
        <link rel="preload" href="{{ asset('storage/' . $service->hero_image) }}" as="image" fetchpriority="high">
    @endif
@endsection

@section('content')

    {{-- Get page content --}}
    @php

        $pageContent = \App\Models\PageContent::where('page_slug', 'weddings')->get();
        $heroTitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'title')->first()?->content_bg ?? 'Сватбена фотография';
        $heroSubtitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'subtitle')->first()?->content_bg ?? 'Създаваме вечни спомени от вашия специален ден';
        $calcTitle = $pageContent->where('section_slug', 'calculator')->where('field_key', 'title')->first()?->content_bg ?? 'Сватбен Калкулатор';
    @endphp

    <!-- Header / Hero -->
    <section class="wedding-hero" @if(!empty($service->hero_image)) style="background-image: url('{{ asset('storage/' . $service->hero_image) }}')" @endif>
        <div class="hero-overlay"></div>
        <div class="hero-title" data-aos="fade-up">
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroSubtitle }}</p>
            @if(!empty($service->video_url))
                <div class="mt-4">
                    <a href="{{ $service->video_url }}" class="glightbox btn-video-play d-inline-flex align-items-center text-decoration-none">
                        <div class="video-play-btn-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-play text-white ms-1" style="font-size: 14px;"></i>
                        </div>
                        <span class="video-play-text text-uppercase fw-bold text-white">Виж нашето видео</span>
                    </a>
                </div>
            @endif
        </div>
    </section>
    @include('partials.breadcrumbs')

    <!-- INTRO -->
    <div class="desc desc1 pt-4 pb-0">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-lg-10 m-auto text-center">
                    <h2 class="pt-3 h3 mb-4">
                        <b>Вашата сватба е един от<br> най-важните дни в живота ви</b>
                    </h2>
                    <div class="section-divider"></div>
                    <p class="py-2">
                        В <b>Take Two Studio 1603</b> предлагаме професионална сватбена фотография и
                        видеозаснемане, които комбинират креативност, стил и персонално отношение към всяка двойка.
                        <br><br>
                        От първия танц до най-малкия детайл – ние ще съхраним вашите емоции, за да ги преживявате отново
                        и отново.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- SERVICES -->
    <div class="desc desc2 py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-12 col-lg-6 m-auto mb-4 mb-lg-0">
                    <div class="row">
                        <div class="col-md-12 col-lg-10 m-auto text-center text-lg-start">
                            <h2 class="mb-3 text-center">Нашите услуги</h2>
                            <div class="section-divider start"></div>
                            <p>
                                <b class="text-uppercase text-primary"><i class="fas fa-camera me-2"></i>
                                    Фотография</b>:<br> Уникални кадри, които улавят емоциите,
                                атмосферата и красотата на вашия ден. <br><br>
                                <b class="text-uppercase text-primary"><i class="fas fa-video me-2"></i>
                                    Видеозаснемане</b>: <br>Професионални видеа с кино качество,
                                които разказват вашата любовна история.<br><br>
                                <b class="text-uppercase text-primary"><i class="far fa-heart me-2"></i> Предсватбени
                                    сесии</b>: <br>Подготвителни снимки, които ви
                                помагат да се чувствате уверени пред камерата.<br><br>
                                <b class="text-uppercase text-primary"><i class="fas fa-magic me-2"></i>
                                    Постпродукция</b>: <br>Фина обработка, за да гарантираме, че
                                всеки кадър е перфектен.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6 m-auto">
                    <img src="{{ asset('css/img/Сватба.jpg') }}" class="w-100 rounded shadow" alt="Сватбен момент">
                </div>
            </div>
        </div>
    </div>

    <!-- WHY US -->
    <section id="why-choose-us" class="py-5 bg-gray">
        <div class="container">
            <div class="row">
                <h2 class="text-center mb-3">Защо да изберете нас?</h2>
                <div class="section-divider"></div>
                <div class="reasons mt-4">
                    <div class="row text-center">
                        <div class="reason col-sm-12 col-md-6 col-lg-3 py-4 px-3" style="cursor: pointer;" onclick="document.getElementById('calculator')?.scrollIntoView({behavior: 'smooth'})">
                            <i class="fas fa-camera-retro fa-3x mb-3"></i>
                            <h3>Професионализъм</h3>
                            <p class="text-muted small">Над 5 години опит в сватбената фотография и видеозаснемане с модерни 4K камери и дрон.</p>
                        </div>
                        <div class="reason col-sm-12 col-md-6 col-lg-3 py-4 px-3" style="cursor: pointer;" onclick="document.getElementById('calculator')?.scrollIntoView({behavior: 'smooth'})">
                            <i class="fas fa-heart fa-3x mb-3"></i>
                            <h3>Емоция и детайли</h3>
                            <p class="text-muted small">Улавяме естествените усмивки, сълзи от щастие и непринудени моменти от вашия специален ден.</p>
                        </div>
                        <div class="reason col-sm-12 col-md-6 col-lg-3 py-4 px-3" style="cursor: pointer;" onclick="document.getElementById('calculator')?.scrollIntoView({behavior: 'smooth'})">
                            <i class="fas fa-lightbulb fa-3x mb-3"></i>
                            <h3>Креативност</h3>
                            <p class="text-muted small">Всяка сватба е уникална. Създаваме сватбени истории, които ще помните завинаги.</p>
                        </div>
                        <div class="reason col-sm-12 col-md-6 col-lg-3 py-4 px-3" style="cursor: pointer; border: 1px solid var(--accent, #d4af37); border-radius: 8px;" onclick="document.getElementById('calculator')?.scrollIntoView({behavior: 'smooth'})">
                            <i class="fas fa-tags fa-3x mb-3"></i>
                            <h3>Достъпни пакети <i class="fas fa-arrow-down small ms-1 text-warning"></i></h3>
                            <p class="text-muted small">Предлагаме гъвкави сватбени пакети на достъпни цени, без компромис с качеството.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($service->video_url))
        <!-- Section - Behind the scenes Video -->
        <section class="video-showcase-section py-5 text-white text-center">
            <div class="container py-4">
                <h2 class="mb-3 text-uppercase fw-bold">Как работим зад кулисите</h2>
                <div class="section-divider"></div>
                <p class="text-muted col-lg-8 mx-auto mb-5">
                    Сватбеният ден е изпълнен с неподправени емоции и магия. Вижте нашето кратко видео, което показва нашия подход, динамика и стил на заснемане на терен.
                </p>
                <div class="video-cover-card mx-auto position-relative rounded shadow-lg overflow-hidden" style="max-width: 800px; aspect-ratio: 16/9;">
                    <img src="{{ !empty($service->hero_image) ? asset('storage/' . $service->hero_image) : asset('css/img/best-wedding-cover.jpg') }}" class="w-100 h-100 object-fit-cover" alt="Видео превю">
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.45);">
                        <a href="{{ $service->video_url }}" class="glightbox video-play-btn-large">
                            <span class="play-btn-ring"></span>
                            <span class="play-btn-icon"><i class="fas fa-play"></i></span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Section - Wedding Galleries -->
    <section class="py-5" id="portfolio">
        <div class="container mt-1">
            <div class="row mb-5">
                <h2 class="text-center text-uppercase fw-bold">Сватбени Галерии</h2>
                <div class="section-divider"></div>
                <h3 class="text-center h5 fw-light text-muted">Разгледайте нашите любими сватбени събития</h3>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach($weddingGalleries as $gallery)
                    <div class="col-md-6 col-lg-4">
                        <!-- Cover Card -->
                        <div class="wedding-gallery-card border-0 rounded position-relative" data-bs-toggle="modal" data-bs-target="#galleryModal{{ $gallery->id }}" style="cursor: pointer;">
                            <div class="gallery-cover-wrapper overflow-hidden rounded shadow-sm" style="height: 320px;">
                                <img src="{{ asset('storage/' . $gallery->cover_image) }}" alt="{{ $gallery->title }}" loading="lazy" class="img-fluid w-100 h-100 object-fit-cover" style="transition: transform 0.4s ease;">
                            </div>
                            <div class="text-center mt-3 p-2">
                                <span class="d-block text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 1px;">Разгледай сватбата на</span>
                                <h4 class="fw-bold mb-2">{{ $gallery->title }}</h4>
                                <span class="btn btn-sm btn-outline-dark rounded-pill px-4 mt-2">Виж Галерията</span>
                            </div>
                        </div>

                        <!-- Modal for Gallery -->
                        <div class="modal fade" id="galleryModal{{ $gallery->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content border-0" style="background: #111;">
                                    <div class="modal-header border-0 py-2 bg-white">
                                        <h5 class="modal-title text-dark fw-bold">Сватбата на {{ $gallery->title }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <!-- Main Carousel -->
                                        <div id="carouselGallery{{ $gallery->id }}" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                <!-- Cover Image first -->
                                                <div class="carousel-item active">
                                                    <img src="{{ asset('storage/' . $gallery->cover_image) }}" class="d-block w-100 object-fit-contain" style="height: 65vh; background: #000;" alt="Сватбена снимка на {{ $gallery->title }} - Take Two Studio">
                                                </div>
                                                <!-- Rest of the photos -->
                                                @foreach($gallery->photos as $photo)
                                                <div class="carousel-item">
                                                    <img src="{{ asset('storage/' . $photo->image_path) }}" class="d-block w-100 object-fit-contain" style="height: 65vh; background: #000;" alt="Сватбена фотография от сватбата на {{ $gallery->title }} - сватбен фотограф Варна">
                                                </div>
                                                @endforeach
                                            </div>
                                            <!-- Controls -->
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0px 0px 4px rgba(0,0,0,0.8));"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0px 0px 4px rgba(0,0,0,0.8));"></span>
                                            </button>
                                        </div>
                                        
                                        <!-- Thumbnails Slider Below -->
                                        <div class="d-flex overflow-auto p-3 custom-scrollbar" style="background: #1a1a1a; gap: 10px;">
                                                <img src="{{ asset('storage/' . $gallery->cover_image) }}" 
                                                    style="height: 70px; width: 100px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 2px solid transparent;" 
                                                    class="gallery-thumbnail hover-border"
                                                    data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide-to="0">
                                                
                                                @foreach($gallery->photos as $index => $photo)
                                                <img src="{{ asset('storage/' . $photo->image_path) }}" 
                                                    style="height: 70px; width: 100px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 2px solid transparent;" 
                                                    class="gallery-thumbnail hover-border"
                                                    data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide-to="{{ $index + 1 }}">
                                                @endforeach
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Modal -->
                    </div>
                @endforeach
                
                @if($weddingGalleries->isEmpty())
                     <div class="col-12 text-center text-muted mb-5">
                        <p>Очаквайте скоро нашите сватбени галерии!</p>
                     </div>
                @endif
            </div>

        </div>
    </section>

    <style>
        .wedding-gallery-card:hover img {
            transform: scale(1.05);
        }
        .wedding-gallery-card:hover .btn-outline-dark {
            background-color: #f39c12;
            color: white;
            border-color: #f39c12;
        }
        .hover-border:hover {
            border-color: #f39c12 !important;
            opacity: 0.8;
        }
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #111;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #f39c12;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable keyboard navigation for all Bootstrap carousels inside modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                    // Find if there is an open modal
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        // Find the carousel inside this modal
                        const carouselElement = openModal.querySelector('.carousel');
                        if (carouselElement) {
                            const carousel = bootstrap.Carousel.getInstance(carouselElement) || new bootstrap.Carousel(carouselElement);
                            
                            if (e.key === 'ArrowLeft') {
                                carousel.prev();
                            } else if (e.key === 'ArrowRight') {
                                carousel.next();
                            }
                        }
                    }
                }
            });
        });
    </script>

    <!-- CALCULATOR SECTION -->
    <section class="calc-section" id="calculator">
        <div class="container">
            <h2 class="text-center mb-4">{{ $calcTitle }}</h2>
            <p class="text-center mb-5" style="max-width: 800px; margin: 0 auto 3rem auto;">
                Изчислете лесно и прозрачно цената за заснемане на вашата сватба. Нашите пакети включват професионално
                фото и 4K видео заснемане, дрон кадри и луксозни фотокниги. Без скрити такси – виждате крайната сума
                веднага.
            </p>
            @php
                $videoPackages = $service->packages->filter(fn($p) => str_contains($p->name_bg, 'Видео'))->sortBy('price_eur');
                $photoPackages = $service->packages->filter(fn($p) => str_contains($p->name_bg, 'Фото'))->sortBy('price_eur');
                
                $photoExtras = $service->extras->where('group_name_bg', 'Фото Добавки');
                $videoExtras = $service->extras->where('group_name_bg', 'Видео Екстри');
                $filmLength = $service->extras->where('group_name_bg', 'Дължина на филма');
                $resolution = $service->extras->where('group_name_bg', 'Резолюция');
                $delivery = $service->extras->where('group_name_bg', 'Получаване');
            @endphp

            @if(session('success'))
                <div class="alert alert-success text-center mb-4">
                    {{ session('success') }}
                </div>
            @endif
            <form id="calcForm" action="{{ url('/submit-order') }}" method="post">
                @csrf
                <div class="row g-5">

                    <!-- LEFT COLUMN: CONTROLS -->
                    <div class="col-lg-8">
                        <div class="calc-card h-100">

                            @if($service && $service->activePromotion)
                                <div class="alert alert-warning border-0 rounded-0 text-center mb-4" style="background: rgba(243, 156, 18, 0.15); color: #f39c12;">
                                    <i class="fas fa-percentage me-2 animate-pulse"></i>
                                    <strong>ПРОМОЦИЯ:</strong> Спестете {{ $service->activePromotion->discount_percent }}% от всички цени до {{ $service->activePromotion->expires_at->format('d.m.Y') }}!
                                </div>
                            @endif

                            <!-- ЕКИП -->
                            <h4 class="mb-4"><i class="fas fa-users me-2 text-warning"></i> Екип</h4>

                            <h6 class="calc-sub-header">Видео Оператори</h6>
                            <div class="row g-2 g-md-3 mb-4">
                                <!-- None Option -->
                                <div class="col-4 col-md-4 px-1 px-md-2">
                                    <input type="radio" name="operators" id="op0" class="package-option calc-input" value="0"
                                        data-price="0" data-label="Видео: Без" data-category="team" onchange="calculateWeddingTotal()">
                                    <label for="op0" class="package-label">
                                        <i class="fas fa-ban package-icon"></i>
                                        <strong>Без</strong>
                                        <p class="extra-description">Без видео заснемане.</p>
                                        <span class="d-block small text-muted mt-1">€ 0</span>
                                    </label>
                                </div>
                                @foreach($videoPackages as $pkg)
                                <div class="col-4 col-md-4 px-1 px-md-2">
                                    @php
                                        $originalPrice = $pkg->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input type="radio" name="operators" id="op{{ $pkg->id }}" class="package-option calc-input" value="{{ $pkg->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $pkg->name_bg }}" data-category="team" 
                                        {{ $pkg->is_default ? 'checked' : '' }} onchange="calculateWeddingTotal()">
                                    <label for="op{{ $pkg->id }}" class="package-label">
                                        <i class="fas fa-video package-icon"></i>
                                        <strong>{{ $pkg->price_eur == 890 ? 'Един' : ($pkg->price_eur == 1145 ? 'Двама' : 'Екип') }}</strong>
                                        <p class="extra-description">{{ $pkg->name_bg }}</p>
                                        <span class="d-block small text-muted mt-1">
                                            @if($service && $service->activePromotion)
                                                <span class="text-decoration-line-through text-muted small me-2">€ {{ number_format($originalPrice, 0) }}</span>
                                                <span class="text-warning">€ {{ number_format($price, 0) }}</span>
                                            @else
                                                € {{ $pkg->price_eur }}
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <h6 class="calc-sub-header">Фотографи</h6>
                            <div class="row g-2 g-md-3 mb-4">
                                <!-- None Option -->
                                <div class="col-4 col-md-4 px-1 px-md-2">
                                    <input type="radio" name="photographers" id="ph0" class="package-option calc-input" value="0"
                                        data-price="0" data-label="Фото: Без" data-category="team" onchange="calculateWeddingTotal()">
                                    <label for="ph0" class="package-label">
                                        <i class="fas fa-ban package-icon"></i>
                                        <strong>Без</strong>
                                        <p class="extra-description">Без фото заснемане.</p>
                                        <span class="d-block small text-muted mt-1">€ 0</span>
                                    </label>
                                </div>
                                @foreach($photoPackages as $pkg)
                                <div class="col-4 col-md-4 px-1 px-md-2">
                                    @php
                                        $originalPrice = $pkg->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input type="radio" name="photographers" id="ph{{ $pkg->id }}" class="package-option calc-input" value="{{ $pkg->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $pkg->name_bg }}" data-category="team"
                                        {{ $pkg->is_default ? 'checked' : '' }} onchange="calculateWeddingTotal()">
                                    <label for="ph{{ $pkg->id }}" class="package-label">
                                        <i class="fas fa-camera package-icon"></i>
                                        <strong>{{ $pkg->price_eur == 890 ? 'Един' : ($pkg->price_eur == 1145 ? 'Двама' : 'Екип') }}</strong>
                                        <p class="extra-description">{{ $pkg->name_bg }}</p>
                                        <span class="d-block small text-muted mt-1">
                                            @if($service && $service->activePromotion)
                                                <span class="text-decoration-line-through text-muted small me-2">€ {{ number_format($originalPrice, 0) }}</span>
                                                <span class="text-warning">€ {{ number_format($price, 0) }}</span>
                                            @else
                                                € {{ $pkg->price_eur }}
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <!-- ФОТО ДОБАВКИ -->
                            <h4 class="mb-4 mt-5"><i class="fas fa-camera me-2 text-warning"></i> Фото Добавки</h4>
                            <div class="row g-3">
                                @foreach($photoExtras as $extra)
                                <div class="col-md-6">
                                    @php
                                        $originalPrice = $extra->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion && $originalPrice > 0) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input class="extra-option calc-input" type="checkbox" id="ex{{ $extra->id }}" value="{{ $extra->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" data-category="photo_extra" onchange="calculateWeddingTotal()">
                                    <label class="extra-card-label" for="ex{{ $extra->id }}">
                                        <i class="fas fa-star extra-card-icon"></i>
                                        <span>{{ $extra->label_bg }}</span>
                                        <p class="extra-description"></p>
                                        <span class="extra-price">
                                            @if($originalPrice > 0)
                                                @if($service && $service->activePromotion)
                                                    <span class="text-decoration-line-through text-muted small me-2">+€ {{ number_format($originalPrice, 0) }}</span>
                                                    <span class="text-warning">+€ {{ number_format($price, 0) }}</span>
                                                @else
                                                    +€ {{ $extra->price_eur }}
                                                @endif
                                            @else
                                                Безплатно
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <!-- ВИДЕО ДОБАВКИ -->
                            <h4 class="mb-4 mt-5"><i class="fas fa-video me-2 text-warning"></i> Видео Добавки</h4>
                            <div class="row g-3">
                                <!-- Film Length -->
                                <div class="col-12"><h6 class="calc-sub-header">Дължина на филма</h6></div>
                                @foreach($filmLength as $extra)
                                <div class="col-md-4">
                                    @php
                                        $originalPrice = $extra->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion && $originalPrice > 0) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input class="extra-option calc-input" type="radio" name="film_length" id="ex{{ $extra->id }}" value="{{ $extra->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" data-category="video_extra" 
                                        {{ $extra->price_eur == 0 ? 'checked' : '' }} onchange="calculateWeddingTotal()">
                                    <label class="extra-card-label" for="ex{{ $extra->id }}">
                                        <i class="fas fa-hourglass-half extra-card-icon"></i>
                                        <span>{{ $extra->label_bg }}</span>
                                        <span class="extra-price">
                                            @if($originalPrice > 0)
                                                @if($service && $service->activePromotion)
                                                    <span class="text-decoration-line-through text-muted small me-2">+€ {{ number_format($originalPrice, 0) }}</span>
                                                    <span class="text-warning">+€ {{ number_format($price, 0) }}</span>
                                                @else
                                                    +€ {{ $extra->price_eur }}
                                                @endif
                                            @else
                                                Стандарт
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach

                                <!-- Resolution -->
                                <div class="col-12 mt-4"><h6 class="calc-sub-header">Резолюция на Видеото</h6></div>
                                @foreach($resolution as $extra)
                                <div class="col-md-6">
                                    @php
                                        $originalPrice = $extra->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion && $originalPrice > 0) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input class="extra-option calc-input" type="radio" name="film_resolution" id="ex{{ $extra->id }}" value="{{ $extra->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" data-category="video_extra"
                                        {{ $extra->price_eur == 0 ? 'checked' : '' }} onchange="calculateWeddingTotal()">
                                    <label class="extra-card-label" for="ex{{ $extra->id }}">
                                        <i class="fas fa-tv extra-card-icon"></i>
                                        <span>{{ $extra->label_bg }}</span>
                                        <span class="extra-price">
                                            @if($originalPrice > 0)
                                                @if($service && $service->activePromotion)
                                                    <span class="text-decoration-line-through text-muted small me-2">+€ {{ number_format($originalPrice, 0) }}</span>
                                                    <span class="text-warning">+€ {{ number_format($price, 0) }}</span>
                                                @else
                                                    +€ {{ $extra->price_eur }}
                                                @endif
                                            @else
                                                Стандарт
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach

                                <!-- Допълнителни Екстри -->
                                <div class="col-12 mt-4"><h6 class="calc-sub-header">Допълнителни Видео Екстри</h6></div>
                                @foreach($videoExtras as $extra)
                                <div class="col-md-4">
                                    @php
                                        $originalPrice = $extra->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion && $originalPrice > 0) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input class="extra-option calc-input" type="checkbox" id="ex{{ $extra->id }}" value="{{ $extra->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" data-category="video_extra" onchange="calculateWeddingTotal()">
                                    <label class="extra-card-label" for="ex{{ $extra->id }}">
                                        <i class="fas fa-plus extra-card-icon"></i>
                                        <span>{{ $extra->label_bg }}</span>
                                        <span class="extra-price">
                                            @if($originalPrice > 0)
                                                @if($service && $service->activePromotion)
                                                    <span class="text-decoration-line-through text-muted small me-2">+€ {{ number_format($originalPrice, 0) }}</span>
                                                    <span class="text-warning">+€ {{ number_format($price, 0) }}</span>
                                                @else
                                                    +€ {{ $extra->price_eur }}
                                                @endif
                                            @else
                                                Стандарт
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <!-- DELIVERY -->
                            <h4 class="mb-4 mt-5"><i class="fas fa-gift me-2 text-warning"></i> Получаване</h4>
                            <div class="row g-3">
                                @foreach($delivery as $extra)
                                <div class="@if($loop->first) col-md-12 @else col-md-6 @endif">
                                    @php
                                        $originalPrice = $extra->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion && $originalPrice > 0) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input class="extra-option calc-input" type="radio" name="recording" id="ex{{ $extra->id }}" value="{{ $extra->id }}"
                                        data-price="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" data-category="delivery"
                                        {{ $extra->price_eur == 0 ? 'checked' : '' }} onchange="calculateWeddingTotal()">
                                    <label class="extra-card-label" for="ex{{ $extra->id }}">
                                        <i class="fas fa-box-open extra-card-icon"></i>
                                        <span>{{ $extra->label_bg }}</span>
                                        <span class="extra-price">
                                            @if($originalPrice > 0)
                                                @if($service && $service->activePromotion)
                                                    <span class="text-decoration-line-through text-muted small me-2">+€ {{ number_format($originalPrice, 0) }}</span>
                                                    <span class="text-warning">+€ {{ number_format($price, 0) }}</span>
                                                @else
                                                    +€ {{ $extra->price_eur }}
                                                @endif
                                            @else
                                                Безплатно
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: RESULT -->
                    <div class="col-lg-4">
                        <div class="total-price-box">
                            <h5 class="text-uppercase text-white-50">Обща Сума</h5>
                            <div class="price-display">€ <span id="finalPrice">0</span></div>
                            <div class="section-divider" style="background: #444; width: 100%;"></div>

                            <div class="text-start mt-4 mb-4">
                                <div class="summary-item"><span>Екип:</span> <span id="sumTeam">-</span></div>
                                <div class="summary-item"><span>Фото Добавки:</span> <span id="sumPhoto">-</span></div>
                                <div class="summary-item"><span>Видео Добавки:</span> <span id="sumVideo">-</span></div>
                                <div class="summary-item" id="promo-discount-line" style="display:none; color:#22c55e;">
                                    <span>Промо Намаление:</span>
                                    <span class="discount-amount" style="font-weight:700;"></span>
                                </div>
                            </div>

                            <input type="hidden" id="hiddenPrice" name="final_price">
                            <input type="hidden" id="hiddenDetails" name="details">
                            <input type="hidden" name="orderType" value="Wedding">

                            @include('partials.promo-code-input')

                            <div class="mt-4">
                                <input type="text" name="name" class="form-control mb-2 rounded-0"
                                    placeholder="Вашето име" required>
                                <input type="tel" name="phone" class="form-control mb-2 rounded-0"
                                    placeholder="Телефон" required>
                                <input type="email" name="email" class="form-control mb-2 rounded-0"
                                    placeholder="Имейл">
                                <input type="date" name="date" class="form-control mb-2 rounded-0"
                                    placeholder="Дата на сватбата" required onclick="this.showPicker()">
                                @include('partials.gdpr-consent', ['consentId' => 'wedding'])
                                <button type="submit" class="btn btn-custom">Изпрати Запитване</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    @if(isset($weddingFaqs) && $weddingFaqs->isNotEmpty())
    <!-- FAQ -->
    <section class="py-5 bg-white">
        <div class="container">
            <h2 class="text-center mb-5">Често Задавани Въпроси</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="weddingFaqAccordion">
                        @foreach($weddingFaqs as $i => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#wfaq{{ $faq->id }}">
                                    {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="wfaq{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#weddingFaqAccordion">
                                <div class="accordion-body text-muted">
                                    {{ $faq->answer }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Validation Modal -->
    <div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning border-0">
                    <h5 class="modal-title text-dark fw-bold"><i class="fas fa-exclamation-circle me-2"></i> Внимание</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    Трябва да изберете поне една основна услуга (Видео или Фотография)!<br><br>
                    <small class="text-muted">За да изчислим пакет и да приемем резервация, е необходимо да изберете поне един екип за заснемане.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Разбрах</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script src="{{ asset('js/calculators/wedding.js') }}"></script>
    <script>
        const lightbox = GLightbox({
            selector: '.glightbox'
        });
    </script>
@endpush

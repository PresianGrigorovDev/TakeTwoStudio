@extends('layouts.app')

@section('title', 'Автомобилна Фотография и Видео Варна | Take Two Studio')
@section('meta_description', 'Професионално фото и видеозаснемане на автомобили във Варна. Заснемане на коли за обяви, автосалони, тунинг проекти и рекламни клипове.')
@section('meta_keywords', 'автомобилен фотограф варна, автомобилно видеозаснемане, car photography варна, видео за коли, тунинг видео, заснемане на автомобили, Take Two Studio')
@section('og_title', 'Автомобилна Фотография и Видео Варна | Take Two Studio')
@section('og_description', 'Професионално заснемане на автомобили — динамични видео клипове, снимки, детайли и интериор. За автосалони, обяви и реклама.')
@section('og_image', asset('css/img/Коли.jpeg'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/baptism.css') }}">
@endpush

@section('preload')
    @if(!empty($service->hero_image))
        <link rel="preload" href="{{ asset('storage/' . $service->hero_image) }}" as="image" fetchpriority="high">
    @endif
@endsection

@section('content')

    @php
        $pageContent = \App\Models\PageContent::where('page_slug', 'automotive')->get();
        $heroTitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'title')->first()?->content_bg ?? 'Автомобилна Фотография';
        $heroSubtitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'subtitle')->first()?->content_bg ?? 'Заснемане на автомобили с внимание към всеки детайл';
    @endphp

    <section class="baptism-hero" @if(!empty($service->hero_image)) style="background-image: url('{{ asset('storage/' . $service->hero_image) }}')" @endif>
        <div class="hero-overlay"></div>
        <div class="hero-title" data-aos="fade-up">
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroSubtitle }}</p>
            @include('partials.video-hero-button')
        </div>
    </section>
    @include('partials.breadcrumbs')

    <!-- INTRO -->
    <section class="py-5 text-center container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h3 class="mb-4 h4 text-muted fw-normal lh-base">
                    Вашият автомобил заслужава кадри, които подчертават всяка линия и детайл.
                </h3>
                <div class="section-divider"></div>
                <p class="text-muted mb-4">
                    В <b>Take Two Studio 1603</b> се специализираме в автомобилна фотография, която представя колата ви
                    в най-добрата светлина — буквално. Работим с естествена и изкуствена светлина, избираме локации,
                    които допълват характера на автомобила, и обработваме всеки кадър с кинематографично внимание
                    към цветовете и контраста. Идеално за автосалони, обяви за продажба, тунинг проекти и лични колекции.
                </p>
            </div>
        </div>
    </section>

    <!-- WHAT WE OFFER -->
    <section class="py-5 bg-gray-light">
        <div class="container">
            <h2 class="text-center mb-5">Какво Предлагаме</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-car moment-icon"></i>
                        <h5>Екстериор</h5>
                        <p class="small text-muted mb-0">Динамични и статични кадри, които подчертават силуета и дизайна.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-cog moment-icon"></i>
                        <h5>Детайли</h5>
                        <p class="small text-muted mb-0">Джанти, емблеми, двигател и тунинг елементи в макро кадри.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-couch moment-icon"></i>
                        <h5>Интериор</h5>
                        <p class="small text-muted mb-0">Кокпит, табло и седалки — представени с перфектна композиция.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-road moment-icon"></i>
                        <h5>На Локация</h5>
                        <p class="small text-muted mb-0">Снимки на пътя, в индустриална среда или на живописен фон.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.video-showcase-section')

    <section class="py-5 bg-white" id="portfolio">
        <div class="container mt-1">
            <div class="row mb-5">
                <h2 class="text-center text-uppercase fw-bold">Автомобилни Галерии</h2>
                <div class="section-divider"></div>
                <h3 class="text-center h5 fw-light text-muted">Разгледайте нашите любими моменти</h3>
            </div>
            <div class="row g-4">
                @foreach($galleries as $gallery)
                    <div class="col-md-6 col-lg-4">
                        <div class="baptism-gallery-card border-0 rounded position-relative" data-bs-toggle="modal" data-bs-target="#galleryModal{{ $gallery->id }}" style="cursor: pointer;">
                            <div class="gallery-cover-wrapper overflow-hidden rounded shadow-sm" style="height: 320px;">
                                <img src="{{ asset('storage/' . $gallery->cover_image) }}" alt="{{ $gallery->title }}" loading="lazy" class="img-fluid w-100 h-100 object-fit-cover" style="transition: transform 0.4s ease;">
                            </div>
                            <div class="text-center mt-3 p-2">
                                <h4 class="fw-bold mb-2">{{ $gallery->title }}</h4>
                                <span class="btn btn-sm btn-outline-dark rounded-pill px-4 mt-2">Виж Галерията</span>
                            </div>
                        </div>
                        <div class="modal fade" id="galleryModal{{ $gallery->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content border-0" style="background: #111;">
                                    <div class="modal-header border-0 py-2 bg-white">
                                        <h5 class="modal-title text-dark fw-bold">{{ $gallery->title }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div id="carouselGallery{{ $gallery->id }}" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                <div class="carousel-item active">
                                                    <img src="{{ asset('storage/' . $gallery->cover_image) }}" class="d-block w-100 object-fit-contain" style="height: 65vh; background: #000;" alt="Снимка на автомобил {{ $gallery->title }} - Take Two Studio">
                                                </div>
                                                @foreach($gallery->photos as $photo)
                                                <div class="carousel-item">
                                                    <img src="{{ asset('storage/' . $photo->image_path) }}" class="d-block w-100 object-fit-contain" style="height: 65vh; background: #000;" alt="Автомобилна фотография Варна - {{ $gallery->title }} - car photography">
                                                </div>
                                                @endforeach
                                            </div>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0px 0px 4px rgba(0,0,0,0.8));"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0px 0px 4px rgba(0,0,0,0.8));"></span>
                                            </button>
                                        </div>
                                        <div class="d-flex overflow-auto p-3 custom-scrollbar" style="background: #1a1a1a; gap: 10px;">
                                            <img src="{{ asset('storage/' . $gallery->cover_image) }}" style="height: 70px; width: 100px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 2px solid transparent;" class="gallery-thumbnail hover-border" data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide-to="0">
                                            @foreach($gallery->photos as $index => $photo)
                                            <img src="{{ asset('storage/' . $photo->image_path) }}" style="height: 70px; width: 100px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 2px solid transparent;" class="gallery-thumbnail hover-border" data-bs-target="#carouselGallery{{ $gallery->id }}" data-bs-slide-to="{{ $index + 1 }}">
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($galleries->isEmpty())
                    <div class="col-12 text-center text-muted mb-5"><p>Очаквайте скоро нашите нови галерии!</p></div>
                @endif
            </div>
        </div>
    </section>

    <style>
        .baptism-gallery-card:hover img { transform: scale(1.05); }
        .baptism-gallery-card:hover .btn-outline-dark { background-color: #f39c12; color: white; border-color: #f39c12; }
        .hover-border:hover { border-color: #f39c12 !important; opacity: 0.8; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #111; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #555; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #f39c12; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        const carouselElement = openModal.querySelector('.carousel');
                        if (carouselElement) {
                            const carousel = bootstrap.Carousel.getInstance(carouselElement) || new bootstrap.Carousel(carouselElement);
                            if (e.key === 'ArrowLeft') carousel.prev();
                            else if (e.key === 'ArrowRight') carousel.next();
                        }
                    }
                }
            });
        });
    </script>

    @if($categoryPackages->count() > 0)
    <section class="calc-section" id="calculator">
        <div class="container">
            <h2 class="text-center mb-4">Калкулатор Автомобилна Фотография</h2>
            <p class="text-center mb-5" style="max-width: 800px; margin: 0 auto 3rem auto;">
                Проверете цената за автомобилна фотосесия във Варна и региона. Предлагаме гъвкави пакети за фото и видео.
            </p>
            @if(session('success'))
                <div class="alert alert-success text-center mb-4">{{ session('success') }}</div>
            @endif
            <form action="{{ url('/submit-order') }}" method="post">
                @csrf
                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="calc-card h-100">
                            @if($service && $service->activePromotion)
                                <div class="alert alert-warning border-0 rounded-0 text-center mb-4" style="background: rgba(243, 156, 18, 0.15); color: #f39c12;">
                                    <i class="fas fa-percentage me-2 animate-pulse"></i>
                                    <strong>ПРОМОЦИЯ:</strong> Спестете {{ $service->activePromotion->discount_percent }}% от всички цени до {{ $service->activePromotion->expires_at->format('d.m.Y') }}!
                                </div>
                            @endif

                            <h4 class="mb-4"><i class="fas fa-camera me-2 text-warning"></i> Избери Услуга</h4>
                            <div class="row g-3 mb-5">
                                @foreach($categoryPackages as $package)
                                <div class="col-md-4">
                                    @php
                                        $originalPrice = $package->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <input type="radio" name="pkg_service" id="pkg_{{ $package->id }}" class="package-option" value="{{ (int)$price }}" data-label="{{ $package->name }}" {{ $package->is_featured ? 'checked' : ($loop->first ? 'checked' : '') }} onchange="calculateGenericTotal()">
                                    <label for="pkg_{{ $package->id }}" class="package-label">
                                        <i class="fas {{ $package->is_featured ? 'fa-star' : 'fa-camera' }} package-icon"></i>
                                        <strong>{{ $package->name }}</strong>
                                        @if($package->description)<span class="d-block small text-muted mt-2">{!! $package->description !!}</span>@endif
                                        <span class="d-block small text-muted mt-1 fw-bold extra-price-tag">
                                            @if($service && $service->activePromotion)
                                                <span class="text-decoration-line-through text-muted small me-2">€ {{ number_format($originalPrice, 0) }}</span>
                                                <span class="text-warning">€ {{ number_format($price, 0) }}</span>
                                            @else
                                                € {{ number_format($originalPrice, 0) }}
                                            @endif
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @php $groupedExtras = ($service && $service->extras) ? $service->extras->groupBy('group_name_bg') : collect(); @endphp
                            @foreach($groupedExtras as $groupName => $extras)
                                @if($groupName)<h4 class="mb-4"><i class="fas fa-plus-circle me-2 text-warning"></i> {{ $groupName }}</h4>@endif
                                <div class="row g-3 mb-5">
                                    @foreach($extras as $extra)
                                    @php
                                        $originalPrice = $extra->price_eur;
                                        $price = $originalPrice;
                                        if ($service && $service->activePromotion && $originalPrice > 0) {
                                            $price = $originalPrice * (1 - ($service->activePromotion->discount_percent / 100));
                                        }
                                    @endphp
                                    <div class="{{ $extra->input_type === 'checkbox' ? 'col-md-4' : 'col-md-6' }}">
                                        <input class="extra-option" type="{{ $extra->input_type }}" name="{{ $extra->input_type === 'radio' ? 'extra_group_' . Str::slug($groupName) : 'extra_' . $extra->id }}" id="extra_{{ $extra->id }}" value="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" {{ $extra->input_type === 'radio' && $loop->first ? 'checked' : '' }} onchange="calculateGenericTotal()">
                                        <label class="extra-card-label" for="extra_{{ $extra->id }}">
                                            <i class="fas {{ $extra->icon_class ?? ($extra->input_type === 'checkbox' ? 'fa-gift' : 'fa-map-marker-alt') }} extra-card-icon"></i>
                                            <span>{{ $extra->label_bg }}</span>
                                            @if($extra->description_bg)<span class="extra-price">{!! $extra->description_bg !!}</span>@endif
                                            <span class="extra-price">
                                                @if($originalPrice > 0)
                                                    @if($service && $service->activePromotion)
                                                        <span class="text-decoration-line-through text-muted small me-2">+€ {{ number_format($originalPrice, 0) }}</span>
                                                        <span class="text-warning">+€ {{ number_format($price, 0) }}</span>
                                                    @else
                                                        +€ {{ number_format($originalPrice, 0) }}
                                                    @endif
                                                @else
                                                    Стандарт
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="total-price-box">
                            <h5 class="text-uppercase text-white-50">Обща Цена</h5>
                            <div class="price-display">€ <span id="finalPrice">0</span></div>
                            <div class="section-divider" style="background: #444; width: 100%;"></div>
                            <div class="text-start mt-4 mb-4">
                                <div class="summary-item"><span>Услуга:</span> <span id="sumService">—</span></div>
                                <div class="summary-item"><span>Обхват:</span> <span id="sumScope">—</span></div>
                                <div class="summary-item"><span>Екстри:</span> <span id="sumExtras">—</span></div>
                            </div>
                            <input type="hidden" id="hiddenPrice" name="final_price">
                            <input type="hidden" id="hiddenDetails" name="details">
                            <input type="hidden" name="orderType" value="Automotive">

                            @include('partials.promo-code-input')
                            <div class="mt-4">
                                <input type="text" name="name" class="form-control mb-2 rounded-0" placeholder="Вашето име" required>
                                <input type="text" name="phone" class="form-control mb-2 rounded-0" placeholder="Телефон" required>
                                <input type="date" name="date" class="form-control mb-2 rounded-0" placeholder="Дата на събитието" required onclick="this.showPicker()">
                                @include('partials.gdpr-consent', ['consentId' => 'automotive'])
                                <button type="submit" class="btn btn-custom">Изпрати Запитване</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    @endif

    <section id="contact" class="py-5 bg-light text-center">
        <div class="container">
            <h2 class="mb-3">Свържете се с нас</h2>
            <div class="section-divider"></div>
            <div class="row justify-content-center mb-5 g-4 mt-2">
                <div class="col-md-4"><i class="fas fa-phone mb-3 text-muted fa-2x"></i><h5 class="fs-6 fw-bold"><a href="tel:{{ \App\Support\Settings::phoneHref(\App\Support\Settings::phone()) }}" class="text-dark">{{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}</a></h5></div>
                <div class="col-md-4"><i class="far fa-envelope mb-3 text-muted fa-2x"></i><h5 class="fs-6 fw-bold"><a href="mailto:{{ \App\Support\Settings::email() }}" class="text-dark">{{ \App\Support\Settings::email() }}</a></h5></div>
                <div class="col-md-4"><i class="fas fa-map-marker-alt mb-3 text-muted fa-2x"></i><h5 class="fs-6 fw-bold">{{ \App\Support\Settings::address() }}</h5></div>
            </div>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="map-container" style="height: 400px; border-radius: 4px; overflow: hidden; border: 1px solid #eee;">
                        <iframe src="https://maps.google.com/maps?q=Take+Two+Studio+1603+Varna&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        const lightbox = GLightbox({
            selector: '.glightbox'
        });
    </script>
    <script src="{{ asset('js/calculators/generic.js') }}"></script>
@endpush

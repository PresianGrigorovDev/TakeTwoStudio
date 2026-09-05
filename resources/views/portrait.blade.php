@extends('layouts.app')

@section('title', 'Портретен фотограф Варна | Take Two Studio 1603')
@section('meta_description', 'Портретен фотограф във Варна – артистични портрети за лична марка, LinkedIn, актьорско портфолио или подарък. Студийно осветление и ретуш.')
@section('meta_keywords', 'портретен фотограф варна, портретна фотосесия варна, бизнес портрет, headshot фотография, лична фотосесия, портрет в студио варна, Take Two Studio')
@section('og_title', 'Портретна Фотография Варна — Разкрийте Своята Уникалност | Take Two Studio')
@section('og_description', 'Артистични индивидуални портрети за лична марка, социални мрежи или подарък. Професионално студийно осветление и ретуш.')
@section('og_image', asset('css/img/about.webp'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/glightbox/glightbox.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/proms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/baptism.css') }}">
@endpush

@php
    $heroUrl = !empty($service->hero_image) ? asset('storage/' . $service->hero_image) : null;
    $heroWebp = \App\Support\Images::webpUrl($heroUrl);
@endphp
@section('preload')
    @if($heroUrl)
        <link rel="preload" href="{{ $heroWebp ?? $heroUrl }}" as="image" fetchpriority="high">
    @endif
@endsection

@section('content')

    @php
        $pageContent = \App\Models\PageContent::where('page_slug', 'portrait')->get();
        $heroTitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'title')->first()?->content_bg ?? 'Портретен фотограф във Варна';
        $heroSubtitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'subtitle')->first()?->content_bg ?? 'Артистични портрети, които разкриват вашата уникалност';
    @endphp

    <!-- HEADER -->
    <section class="baptism-hero" @if($heroUrl) style="background-image: url('{{ $heroUrl }}');{{ $heroWebp ? " background-image: image-set(url('{$heroWebp}') type('image/webp'), url('{$heroUrl}') type('image/jpeg'));" : '' }}" @endif>
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
                    Портретът е огледало на личността — ние го превръщаме в изкуство.
                </h3>
                <div class="section-divider"></div>
                <p class="text-muted mb-4">
                    В <b>Take Two Studio 1603</b> създаваме портрети, които разказват вашата история. Независимо дали ви трябва
                    професионален headshot за LinkedIn, актьорско портфолио, бизнес портрет или просто искате да се почувствате
                    специални — ние работим с професионално студийно осветление, внимание към детайла и индивидуален подход
                    към всеки клиент.
                </p>
            </div>
        </div>
    </section>

    <!-- WHAT WE OFFER -->
    <section class="py-5 bg-gray-light">
        <div class="container">
            <h2 class="text-center mb-5">Видове Портретни Сесии</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-briefcase moment-icon"></i>
                        <h5>Бизнес Портрет</h5>
                        <p class="small text-muted mb-0">Професионални headshot снимки за LinkedIn, CV и корпоративен профил.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-palette moment-icon"></i>
                        <h5>Артистичен Портрет</h5>
                        <p class="small text-muted mb-0">Креативни концепции с драматично осветление и уникална визия.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-gift moment-icon"></i>
                        <h5>Портрет Подарък</h5>
                        <p class="small text-muted mb-0">Перфектният подарък за рожден ден, годишнина или специален повод.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="moment-card">
                        <i class="fas fa-magic moment-icon"></i>
                        <h5>Ретуш и Стил</h5>
                        <p class="small text-muted mb-0">Професионална обработка, цветова корекция и кожен ретуш.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- GALLERY -->
    @include('partials.video-showcase-section')

    <section class="py-5 bg-white" id="portfolio">
        <div class="container mt-1">
            <div class="row mb-5">
                <h2 class="text-center text-uppercase fw-bold">Портфолио</h2>
                <div class="section-divider"></div>
                <h3 class="text-center h5 fw-light text-muted">Вижте част от нашите артистични портрети</h3>
            </div>

            @php $galleryLimit = 12; @endphp
            <div class="masonry" id="portraitGallery" data-aos="fade-up">
                @foreach($portraitPortfolioPhotos as $i => $photo)
                    <div class="masonry-item gallery-item @if($i >= $galleryLimit) gallery-hidden @endif">
                        <div class="portfolio-item">
                            <a href="{{ Storage::url($photo->image_path) }}" class="glightbox">
                                <img loading="lazy" src="{{ Storage::url($photo->image_path) }}" class="portfolio-img" alt="Портретна Фотография Варна - Снимка {{ $i + 1 }} - Take Two Studio">
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($portraitPortfolioPhotos->count() > $galleryLimit)
                <div class="text-center mt-4">
                    <button type="button" class="btn-custom" id="loadMoreBtn" onclick="loadMorePhotos()">Виж още</button>
                </div>
            @endif

            @if($portraitPortfolioPhotos->isEmpty())
                <div class="col-12 text-center text-muted mb-5">
                    <p>Очаквайте скоро нашите нови портрети!</p>
                </div>
            @endif
        </div>
    </section>


    @push('scripts')
        <script src="{{ asset('vendor/glightbox/glightbox.min.js') }}"></script>
        <script src="{{ asset('js/calculators/generic.js') }}"></script>
        <script>
            const lightbox = GLightbox({
                selector: '.glightbox'
            });

            function loadMorePhotos() {
                var hidden = document.querySelectorAll('#portraitGallery .gallery-hidden');
                var step = {{ $galleryLimit }};

                for (var i = 0; i < step && i < hidden.length; i++) {
                    hidden[i].classList.remove('gallery-hidden');
                }

                if (document.querySelectorAll('#portraitGallery .gallery-hidden').length === 0) {
                    document.getElementById('loadMoreBtn').style.display = 'none';
                }

                GLightbox({ selector: '.glightbox' });
            }
        </script>
    @endpush

    <!-- CALCULATOR -->
    @if($categoryPackages->count() > 0)
    <section class="calc-section" id="calculator">
        <div class="container">
            <h2 class="text-center mb-4">Калкулатор Портретна Фотография</h2>
            <p class="text-center mb-5 intro-text">
                Проверете цената за портретна фотосесия във Варна и региона. Предлагаме гъвкави пакети за фото и видео.
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
                                <div class="alert alert-warning border-0 rounded-0 text-center mb-4 badge-gold">
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
                                    <input type="radio" name="pkg_service" id="pkg_{{ $package->id }}" class="package-option"
                                        value="{{ (int)$price }}" data-label="{{ $package->name }}" {{ $package->is_featured ? 'checked' : ($loop->first ? 'checked' : '') }} onchange="calculateGenericTotal()">
                                    <label for="pkg_{{ $package->id }}" class="package-label">
                                        <i class="fas {{ $package->is_featured ? 'fa-star' : 'fa-camera' }} package-icon"></i>
                                        <strong>{{ $package->name }}</strong>
                                        @if($package->description)
                                        <span class="d-block small text-muted mt-2">{!! $package->description !!}</span>
                                        @endif
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
                                @if($groupName)
                                <h4 class="mb-4"><i class="fas fa-plus-circle me-2 text-warning"></i> {{ $groupName }}</h4>
                                @endif
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
                                        <input class="extra-option" type="{{ $extra->input_type }}" name="{{ $extra->input_type === 'radio' ? 'extra_group_' . Str::slug($groupName) : 'extra_' . $extra->id }}" id="extra_{{ $extra->id }}"
                                            value="{{ (int)$price }}" data-label="{{ $extra->label_bg }}" {{ $extra->input_type === 'radio' && $loop->first ? 'checked' : '' }} onchange="calculateGenericTotal()">
                                        <label class="extra-card-label" for="extra_{{ $extra->id }}">
                                            <i class="fas {{ $extra->icon_class ?? ($extra->input_type === 'checkbox' ? 'fa-gift' : 'fa-map-marker-alt') }} extra-card-icon"></i>
                                            <span>{{ $extra->label_bg }}</span>
                                            @if($extra->description_bg)
                                            <span class="extra-price">{!! $extra->description_bg !!}</span>
                                            @endif
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
                                <div class="summary-item" id="promo-discount-line" style="display:none; color:#22c55e;">
                                    <span>Промо Намаление:</span>
                                    <span class="discount-amount" style="font-weight:700;"></span>
                                </div>
                            </div>
                            <input type="hidden" id="hiddenPrice" name="final_price">
                            <input type="hidden" id="hiddenDetails" name="details">
                            <input type="hidden" name="orderType" value="Portrait">

                            @include('partials.promo-code-input')

                            <div class="mt-4">
                                <input type="text" name="name" class="form-control mb-2 rounded-0" placeholder="Вашето име" required>
                                <input type="text" name="phone" class="form-control mb-2 rounded-0" placeholder="Телефон" required>
                                <input type="date" name="date" class="form-control mb-2 rounded-0" placeholder="Дата на събитието" required onclick="this.showPicker()">
                                @include('partials.gdpr-consent', ['consentId' => 'portrait'])
                                <button type="submit" class="btn btn-custom">Изпрати Запитване</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    @endif

    <!-- CONTACT -->
    <section id="contact" class="py-5 bg-light text-center">
        <div class="container">
            <h2 class="mb-3">Свържете се с нас</h2>
            <div class="section-divider"></div>
            <div class="row justify-content-center mb-5 g-4 mt-2">
                <div class="col-md-4">
                    <i class="fas fa-phone mb-3 text-muted fa-2x"></i>
                    <h5 class="fs-6 fw-bold"><a href="tel:{{ \App\Support\Settings::phoneHref(\App\Support\Settings::phone()) }}" class="text-dark">{{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}</a></h5>
                </div>
                <div class="col-md-4">
                    <i class="far fa-envelope mb-3 text-muted fa-2x"></i>
                    <h5 class="fs-6 fw-bold"><a href="mailto:{{ \App\Support\Settings::email() }}" class="text-dark">{{ \App\Support\Settings::email() }}</a></h5>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-map-marker-alt mb-3 text-muted fa-2x"></i>
                    <h5 class="fs-6 fw-bold">{{ \App\Support\Settings::address() }}</h5>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="map-container" style="height: 400px; border-radius: 4px; overflow: hidden; border: 1px solid #eee;">
                            @include('partials.map-embed')
</div>
                </div>
            </div>
        </div>
    </section>
@endsection


@push('scripts')
    {{-- Scripts already pushed above --}}
@endpush

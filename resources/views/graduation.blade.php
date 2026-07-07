@extends('layouts.app')

@section('title', 'Пред-бална Фотосесия Варна | Изпращане на Абитуриент | Take Two Studio 1603')
@section('meta_description', 'Запечатайте семейния момент преди бала! Пред-бална фотосесия за изпращане на абитуриент във Варна — семейни портрети, индивидуални кадри и невероятни спомени. Резервирайте своята дата.')
@section('meta_keywords', 'пред-бална фотосесия варна, изпращане абитуриент, семейна фотосесия бал варна, фотограф абитуриентско изпращане, снимки изпращане от семейството варна, Take Two Studio 1603')
@section('og_title', 'Пред-бална Фотосесия | Изпращане на Абитуриент — Take Two Studio 1603')
@section('og_description', 'Моментът преди бала е само ваш. Семейна фотосесия за изпращане на вашия абитуриент — елегантна, емоционална и незабравима.')
@section('og_image', asset('css/img/prom.jpg'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/graduation.css') }}">
@endpush

@section('content')

{{-- Get page content --}}
@php
    $pageContent = \App\Models\PageContent::where('page_slug', 'graduation')->get();
@endphp

{{-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ --}}
<section class="graduation-hero" @if(!empty($service->hero_image)) style="background-image: url('{{ asset('storage/' . $service->hero_image) }}')" @endif>
    <div class="hero-overlay"></div>
    <div class="hero-content" data-aos="fade-up">
        <span class="hero-badge">Take Two Studio 1603</span>
        <h1>{{ $pageContent[0]->content_bg }}</h1>
        <span class="hero-subtitle">{{ $pageContent[1]->content_bg }}</span>
        <div>
            <a href="#contact" class="btn-grad-primary">Резервирай дата</a>
            <a href="#gallery" class="btn-grad-outline">Виж портфолиото</a>
        </div>
        @include('partials.video-hero-button')
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     INTRO
══════════════════════════════════════════════════════════ --}}
<section class="intro-section py-5">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center" data-aos="fade-up">
                <h2 class="mb-3">Изпращането от Семейството</h2>
                <div class="section-divider"></div>
                <p class="lead-text mb-4">
                    Преди да тръгне към бала, той или тя ще бъдат само ваши — за последно в детската стая,
                    сред семейния дом, заобиколени от хората, които ги обичат най-много.
                    Тези минути са невъзвратими. Ние ги запечатваме завинаги.
                </p>
                <blockquote>
                    „Снимките от изпращането станаха най-скъпите ни семейни спомени."
                </blockquote>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     BENEFITS
══════════════════════════════════════════════════════════ --}}
<section class="benefit-section py-5">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="mb-3">Защо да изберете нас</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fas fa-heart"></i></div>
                    <h5>Емоционален подход</h5>
                    <p>Работим деликатно, за да уловим истинската атмосфера на вашия семеен момент.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fas fa-camera"></i></div>
                    <h5>Кинематографично качество</h5>
                    <p>Снимаме с професионална техника — резкост, светлина и цвят на кинематографично ниво.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h5>Локация по ваш избор</h5>
                    <p>У дома, в градинката, на любимото място — снимаме там, където се чувствате най-добре.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fas fa-clock"></i></div>
                    <h5>Бърза доставка</h5>
                    <p>Обработените снимки получавате в онлайн галерия до 5 работни дни след сесията.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════════════════════════ --}}
<section class="process-section py-5">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="mb-3">Как протича сесията</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-3 step-connector" data-aos="fade-up" data-aos-delay="0">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Резервирайте дата</h5>
                    <p>Свържете се с нас поне 3-4 седмици преди бала, за да сме сигурни, че датата е свободна.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 step-connector" data-aos="fade-up" data-aos-delay="100">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>Избор на локация</h5>
                    <p>Обсъждаме заедно най-подходящото място — дом, парк или друга значима за вас локация.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 step-connector" data-aos="fade-up" data-aos-delay="200">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>Денят на сесията</h5>
                    <p>Снимаме 60–90 минути преди тръгването за бала, улавяйки всяка емоция и усмивка.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h5>Получавате снимките</h5>
                    <p>До 5 дни получавате достъп до онлайн галерия с всички обработени кадри.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     GALLERY
══════════════════════════════════════════════════════════ --}}
    @include('partials.video-showcase-section')

<section class="py-5 bg-white" id="portfolio">
    <div class="container">
        <h2 class="text-center mb-5">Разгледайте нашите любими кадри от семейни изпращания</h2>
        <div class="section-divider"></div>

        <div class="masonry" data-aos="fade-up">
            @forelse($graduationPhotos as $photo)
            <div class="masonry-item">
                <div class="portfolio-item">
                    <a href="{{ Storage::url($photo->image_path) }}" class="glightbox"
                       data-title="{{ $photo->alt_text ?? 'Пред-бална Фотосесия' }}"
                       data-description="{{ $photo->description ?? '' }}">
                        <img loading="lazy" src="{{ Storage::url($photo->image_path) }}" class="portfolio-img"
                             alt="{{ $photo->alt_text ?? 'Пред-бална фотосесия Варна' }}">
                    </a>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-camera fa-3x mb-3 d-block" style="color:#ddd;"></i>
                <p>Очаквайте скоро нашите първи кадри от пред-балните сесии!</p>
            </div>
            @endforelse
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     CALCULATOR
══════════════════════════════════════════════════════════ --}}
<section class="calc-section" id="pricing">
    <div class="container">
        <h2 class="text-center mb-4">{{ $pageContent[2]->content_bg }}</h2>
        <p class="text-center mb-5" style="max-width:800px; margin:0 auto 3rem auto;">
            Изберете пакет и желаните добавки — цената се изчислява автоматично.
            Имате нестандартни изисквания? <a href="#contact" style="color:var(--accent); font-weight:600; text-decoration:none;">Пишете ни за индивидуална оферта.</a>
        </p>

        @if(session('success'))
            <div class="alert alert-success text-center mb-4">{{ session('success') }}</div>
        @endif

        <form id="graduationCalcForm" action="{{ url('/submit-order') }}" method="POST">
            @csrf
            <input type="hidden" name="orderType" value="Graduation">

            <div class="row g-5">

                {{-- LEFT: пакети + екстри --}}
                <div class="col-lg-8">
                    <div class="calc-card h-100">

                        @if($service && $service->activePromotion)
                            @php $promo = $service->activePromotion; @endphp
                            <div class="alert alert-warning border-0 rounded-0 text-center mb-4" style="background: rgba(243, 156, 18, 0.15); color: #f39c12;">
                                <i class="fas fa-percentage me-2 animate-pulse"></i>
                                @if($promo->discount_type === 'fixed')
                                    <strong>ПРОМОЦИЯ:</strong> Специална цена {{ number_format($promo->discount_amount, 0) }}€ на пакет до {{ $promo->expires_at->format('d.m.Y') }}!
                                @else
                                    <strong>ПРОМОЦИЯ:</strong> Спестете {{ $promo->discount_percent }}% от всички цени до {{ $promo->expires_at->format('d.m.Y') }}!
                                @endif
                            </div>
                        @endif

                        {{-- 1. Пакети --}}
                        <h4 class="mb-4 text-center"><i class="fas fa-crown me-2 text-warning"></i> Избери Пакет</h4>

                        @if($graduationPackages->isEmpty())
                            <p class="text-center text-muted">Очаквайте скоро нашите пакети.</p>
                        @else
                        @php
                            $pkgCount = $graduationPackages->count();
                            $pkgCol = match(true) {
                                $pkgCount === 1 => 'col-md-8 mx-auto',
                                $pkgCount === 2 => 'col-md-6',
                                $pkgCount >= 3  => 'col-md-6',
                                default         => 'col-md-6',
                            };
                        @endphp
                        <div class="row g-4 mb-5 justify-content-center">
                            @foreach($graduationPackages as $i => $pkg)
                            @php
                                $originalPrice = $pkg->price_eur;
                                $price = $originalPrice;
                                if ($service && $service->activePromotion) {
                                    $promo = $service->activePromotion;
                                    if ($promo->discount_type === 'fixed' && $promo->service_package_id == $pkg->id) {
                                        $price = (float) $promo->discount_amount;
                                    } elseif ($promo->discount_type === 'percent') {
                                        $price = $originalPrice * (1 - ($promo->discount_percent / 100));
                                    }
                                }
                            @endphp
                            <div class="{{ $pkgCol }}">
                                <input type="radio" name="grad_package"
                                       id="pkg_{{ $pkg->id }}"
                                       class="package-option"
                                       value="{{ (int)$price }}"
                                       data-label="{{ $pkg->name }}"
                                       {{ $i === 0 ? 'checked' : '' }}
                                       onchange="calculateGraduationTotal()">
                                <label for="pkg_{{ $pkg->id }}"
                                       class="package-label {{ $pkg->is_featured ? 'lux-pack' : '' }}">
                                    <i class="fas {{ $pkg->is_featured ? 'fa-star' : 'fa-camera' }} package-icon"></i>
                                    <strong>{{ $pkg->name }}</strong>
                                    <span class="price-tag">
                                        @if($price != $originalPrice)
                                            <span class="text-decoration-line-through text-muted small me-2">€ {{ number_format($originalPrice, 0) }}</span>
                                            <span class="text-warning">€ {{ number_format($price, 0) }} / {{ number_format($price * 1.9558, 2) }} лв.</span>
                                        @else
                                            € {{ number_format($originalPrice, 0) }} / {{ number_format($originalPrice * 1.9558, 2) }} лв.
                                        @endif
                                    </span>
                                    @if($pkg->description)
                                        <p style="font-size:.8rem; color:#777; margin:8px 0 0; text-align:center;">{{ $pkg->description }}</p>
                                    @endif
                                    @if(!empty($pkg->features))
                                    <ul class="package-details">
                                        @foreach($pkg->features as $feat)
                                            <li><i class="fas fa-check small"></i> {{ $feat }}</li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- 2. Екстри --}}
                        <h4 class="mb-4 text-center"><i class="fas fa-book-open me-2 text-warning"></i> Добавки</h4>
                        @php
                            $extraVideoOriginal = 62;
                            $extraVideoPrice = $extraVideoOriginal;
                            $extraExpressOriginal = 26;
                            $extraExpressPrice = $extraExpressOriginal;
                            $extraAlbumOriginal = 41;
                            $extraAlbumPrice = $extraAlbumOriginal;

                            if ($service && $service->activePromotion && $service->activePromotion->discount_type === 'percent') {
                                $extraVideoPrice = $extraVideoOriginal * (1 - ($service->activePromotion->discount_percent / 100));
                                $extraExpressPrice = $extraExpressOriginal * (1 - ($service->activePromotion->discount_percent / 100));
                                $extraAlbumPrice = $extraAlbumOriginal * (1 - ($service->activePromotion->discount_percent / 100));
                            }
                        @endphp
                        <div class="row g-3 justify-content-center">

                            <div class="col-md-4">
                                <input type="checkbox" class="extra-option" id="extra_video"
                                       value="{{ (int)$extraVideoPrice }}" data-label="Кратко видео (1–2 мин.)"
                                       onchange="calculateGraduationTotal()">
                                <label class="extra-card-label" for="extra_video">
                                    <i class="fas fa-video extra-card-icon"></i>
                                    <span class="fw-bold">Кратко видео</span>
                                    <span class="extra-price">
                                        @if($extraVideoPrice != $extraVideoOriginal)
                                            <span class="text-decoration-line-through text-muted small me-2">+€ {{ $extraVideoOriginal }}</span>
                                            <span class="text-warning">+€ {{ number_format($extraVideoPrice, 0) }} / {{ number_format($extraVideoPrice * 1.9558, 2) }} лв.</span>
                                        @else
                                            +€ 62 / {{ number_format(62 * 1.9558, 2) }} лв.
                                        @endif
                                    </span>
                                </label>
                            </div>

                            <div class="col-md-4">
                                <input type="checkbox" class="extra-option" id="extra_express"
                                       value="{{ (int)$extraExpressPrice }}" data-label="Express обработка (до 2 дни)"
                                       onchange="calculateGraduationTotal()">
                                <label class="extra-card-label" for="extra_express">
                                    <i class="fas fa-bolt extra-card-icon"></i>
                                    <span class="fw-bold">Express обработка</span>
                                    <span class="extra-price">
                                        @if($extraExpressPrice != $extraExpressOriginal)
                                            <span class="text-decoration-line-through text-muted small me-2">+€ {{ $extraExpressOriginal }}</span>
                                            <span class="text-warning">+€ {{ number_format($extraExpressPrice, 0) }} / {{ number_format($extraExpressPrice * 1.9558, 2) }} лв.</span>
                                        @else
                                            +€ 26 / {{ number_format(26 * 1.9558, 2) }} лв.
                                        @endif
                                    </span>
                                </label>
                            </div>

                            <div class="col-md-4">
                                <input type="checkbox" class="extra-option" id="extra_album"
                                       value="{{ (int)$extraAlbumPrice }}" data-label="Семеен фотоалбум"
                                       onchange="calculateGraduationTotal()">
                                <label class="extra-card-label" for="extra_album">
                                    <i class="fas fa-book-open extra-card-icon"></i>
                                    <span class="fw-bold">Семеен фотоалбум</span>
                                    <span class="extra-price">
                                        @if($extraAlbumPrice != $extraAlbumOriginal)
                                            <span class="text-decoration-line-through text-muted small me-2">+€ {{ $extraAlbumOriginal }}</span>
                                            <span class="text-warning">+€ {{ number_format($extraAlbumPrice, 0) }} / {{ number_format($extraAlbumPrice * 1.9558, 2) }} лв.</span>
                                        @else
                                            +€ 41 / {{ number_format(41 * 1.9558, 2) }} лв.
                                        @endif
                                    </span>
                                </label>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- RIGHT: обобщение + форма --}}
                <div class="col-lg-4">
                    <div class="total-price-box">
                        <h5 class="text-uppercase text-white-50">Цена</h5>
                        @php
                            $firstPkg = $graduationPackages->first();
                            $firstPkgPrice = $firstPkg?->price_eur ?? 0;
                            if ($service && $service->activePromotion) {
                                $promo = $service->activePromotion;
                                if ($promo->discount_type === 'fixed' && $promo->service_package_id == $firstPkg?->id) {
                                    $firstPkgPrice = (float) $promo->discount_amount;
                                } elseif ($promo->discount_type === 'percent') {
                                    $firstPkgPrice = $firstPkgPrice * (1 - ($promo->discount_percent / 100));
                                }
                            }
                        @endphp
                        <div class="price-display">€ <span id="finalPrice">{{ (int)$firstPkgPrice }}</span> / <span id="finalPriceBgn">{{ number_format($firstPkgPrice * 1.9558, 2) }}</span> лв.</div>
                        <div class="section-divider" style="background:#444; width:100%;"></div>

                        <div class="text-start mt-4 mb-4">
                            <div class="summary-item"><span>Пакет:</span> <span id="sumPackage">{{ $graduationPackages->first()?->name ?? '—' }}</span></div>
                            <div class="summary-item"><span>Добавки:</span> <span id="sumExtras">—</span></div>
                            <div class="summary-item" id="promo-discount-line" style="display:none; color:#22c55e;">
                                <span>Промо Намаление:</span>
                                <span class="discount-amount" style="font-weight:700;"></span>
                            </div>
                        </div>

                        <input type="hidden" id="hiddenPrice" name="final_price">
                        <input type="hidden" id="hiddenDetails" name="details">

                        @include('partials.promo-code-input')

                        <div class="mt-4">
                            <input type="text" name="name" class="form-control mb-2 rounded-0"
                                   placeholder="Вашето име" required>
                            <input type="tel" name="phone" class="form-control mb-2 rounded-0"
                                   placeholder="Телефон" required>
                            <input type="date" name="graduation_date" id="graduationDate" class="form-control mb-2 rounded-0"
                                   placeholder="Дата на изпращането" required onclick="this.showPicker()">
                            <input type="hidden" name="graduation_start_time" id="graduationStartTime">
                            <input type="hidden" name="graduation_end_time" id="graduationEndTime">
                            <div id="graduationHoursWrapper" class="mb-2" style="display:none">
                                <small class="text-white-50 d-block mb-1" id="graduationHoursLabel">Изберете начален час:</small>
                                <div id="graduationHoursGrid" class="graduation-hours-grid"></div>
                                <div id="graduationTimeDisplay" class="graduation-time-display" style="display:none"></div>
                            </div>
                            @include('partials.gdpr-consent', ['consentId' => 'graduation'])
                            <button type="submit" class="btn-custom">Изпрати Запитване</button>
                        </div>

                        <p class="price-note mt-3 mb-0">
                            * 50% капаро при резервация. Остатъкът – до деня на сесията.
                        </p>
                    </div>
                </div>

            </div>
        </form>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     FAQ — за Google Featured Snippets
══════════════════════════════════════════════════════════ --}}
<section class="faq-section py-5" id="faq">
    <div class="container py-4">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="mb-3">Често задавани въпроси</h2>
            <div class="section-divider"></div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                @if($graduationFaqs->isEmpty())
                    <p class="text-center text-muted">Очаквайте скоро.</p>
                @else
                <div class="accordion" id="faqAccordion">
                    @foreach($graduationFaqs as $i => $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faq{{ $faq->id }}">
                                    {{ $faq->question }}
                                </button>
                            </h3>
                            <div id="faq{{ $faq->id }}"
                                 class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {{ $faq->answer }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     CONTACT CTA (anchor за navbar линк)
══════════════════════════════════════════════════════════ --}}
<div id="contact"></div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script src="{{ asset('js/calculators/graduation.js') }}"></script>
    <script>
        const graduationLightbox = GLightbox({ selector: '.glightbox' });
    </script>
    <script>
    (function() {
        var dateInput = document.getElementById('graduationDate');
        var wrapper = document.getElementById('graduationHoursWrapper');
        var grid = document.getElementById('graduationHoursGrid');
        var label = document.getElementById('graduationHoursLabel');
        var timeDisplay = document.getElementById('graduationTimeDisplay');
        var startInput = document.getElementById('graduationStartTime');
        var endInput = document.getElementById('graduationEndTime');
        var selectedStart = null;
        var selectedEnd = null;
        var availableHours = [];

        dateInput.addEventListener('change', function() {
            if (!this.value) { wrapper.style.display = 'none'; return; }
            wrapper.style.display = 'block';
            grid.innerHTML = '<small class="text-white-50">Зареждане...</small>';
            selectedStart = null; selectedEnd = null;
            startInput.value = ''; endInput.value = '';
            timeDisplay.style.display = 'none';
            label.textContent = 'Изберете начален час:';

            fetch('/api/booking-hours?date=' + this.value)
                .then(function(r) { return r.json(); })
                .then(function(hours) {
                    availableHours = hours;
                    renderHours(hours);
                });
        });

        function renderHours(hours) {
            grid.innerHTML = '';
            if (hours.length === 0) {
                grid.innerHTML = '<small class="text-white-50">Няма свободни часове</small>';
                return;
            }
            hours.forEach(function(h) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'grad-hour-btn';
                btn.textContent = h;
                btn.dataset.hour = h;
                btn.addEventListener('click', onHourClick);
                grid.appendChild(btn);
            });
        }

        function onHourClick(e) {
            var hour = e.currentTarget.dataset.hour;
            if (!selectedStart) {
                selectedStart = hour;
                startInput.value = hour;
                label.textContent = 'Изберете краен час:';
                var startH = parseInt(hour);
                var btns = grid.querySelectorAll('.grad-hour-btn');
                btns.forEach(function(btn) {
                    var btnH = parseInt(btn.dataset.hour);
                    btn.classList.remove('selected', 'in-range', 'disabled');
                    if (btnH === startH) { btn.classList.add('selected'); }
                    else if (btnH < startH) { btn.classList.add('disabled'); btn.disabled = true; }
                    else {
                        var ok = true;
                        for (var hh = startH; hh <= btnH; hh++) {
                            if (availableHours.indexOf(String(hh).padStart(2,'0') + ':00') === -1) { ok = false; break; }
                        }
                        if (!ok) { btn.classList.add('disabled'); btn.disabled = true; }
                    }
                });
            } else {
                var endH = parseInt(hour);
                selectedEnd = String(endH).padStart(2,'0') + ':00';
                endInput.value = selectedEnd;
                timeDisplay.style.display = 'block';
                timeDisplay.textContent = selectedStart + ' – ' + selectedEnd;
                var startH = parseInt(selectedStart);
                var btns = grid.querySelectorAll('.grad-hour-btn');
                btns.forEach(function(btn) {
                    var btnH = parseInt(btn.dataset.hour);
                    btn.classList.remove('in-range');
                    if (btnH >= startH && btnH <= endH) btn.classList.add('in-range');
                });
            }
        }

        if (timeDisplay) {
            timeDisplay.addEventListener('click', function() {
                if (dateInput.value) dateInput.dispatchEvent(new Event('change'));
            });
        }
    })();
    </script>
@endpush

@push('schema')
@php
$serviceSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => 'Пред-бална Фотосесия — Изпращане на Абитуриент',
    'description' => 'Семейна фотосесия преди абитуриентски бал. Запечатваме момента на изпращането от семейството с професионални снимки.',
    'provider' => [
        '@type' => 'ProfessionalService',
        'name' => 'Take Two Studio 1603',
        'url' => 'https://taketwostudio1603.com',
        'telephone' => '+359886190124',
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Varna', 'addressCountry' => 'BG'],
    ],
    'areaServed' => ['@type' => 'City', 'name' => 'Varna'],
    'serviceType' => 'Photography',
    'url' => 'https://taketwostudio1603.com/graduation',
    'offers' => $graduationPackages->map(fn($pkg) => [
        '@type' => 'Offer',
        'name' => $pkg->name,
        'price' => (string) $pkg->price_eur,
        'priceCurrency' => 'EUR',
    ])->values()->toArray(),
];
@endphp
<script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@if($graduationFaqs->isNotEmpty())
@php
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $graduationFaqs->map(fn($faq) => [
        '@type' => 'Question',
        'name' => $faq->question,
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
    ])->values()->toArray(),
];
@endphp
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

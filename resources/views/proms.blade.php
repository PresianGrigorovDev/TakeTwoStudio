@extends('layouts.app')

@section('title', 'Фотограф за Абитуриентски Бал Варна | Видео и Фотосесии | Take Two Studio 1603')
@section('meta_description', 'Запечатайте емоцията на завършването! Професионален фотограф за абитуриентски бал във Варна и региона. Индивидуални фотосесии, 4K видео клипове и дрон. Запазете час сега!')
@section('meta_keywords', 'фотограф за бал варна, абитуриентска фотосесия, видеозаснемане бал, фотосесия морска градина, снимки абитуриенти, Take Two Studio')
@section('og_title', 'Фотосесия за Абитуриентски Бал | Take Two Studio Варна')
@section('og_description', 'Искате уникални кадри от бала? Вижте портфолиото ни с абитуриенти и запазете дата за вашата фотосесия.')
@section('og_image', asset('css/img/prom-photoshoot-varna.jpg'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/proms.css') }}">
@endpush

@section('content')

{{-- Get page content --}}
@php
    $pageContent = \App\Models\PageContent::where('page_slug', 'proms')->get();
    $heroTitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'title')->first()?->content_bg ?? 'Направи бала незабравим';
    $heroSubtitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'subtitle')->first()?->content_bg ?? 'Създаваме вечни спомени от вашия специален ден';
    $calcTitle = $pageContent->where('section_slug', 'calculator')->where('field_key', 'title')->first()?->content_bg ?? 'Абитуриентски Калкулатор';
@endphp

    <!-- HEADER -->
    <section class="prom-hero" @if(!empty($service->hero_image)) style="background-image: url('{{ asset('storage/' . $service->hero_image) }}')" @endif>
        <div class="hero-overlay"></div>
        <div class="hero-title" data-aos="fade-up">
            <h1>{{ $heroTitle }}</h1>
            <p>Създаваме вечни спомени от вашия специален ден</p>
        </div>
    </section>

    <!-- INTRO -->
    <section class="pt-4 pb-5 text-center container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <h2 class="mb-4 h2 text-black fw-normal lh-base">
                    <b>Абитуриентският бал е един от най-вълнуващите моменти в живота на всеки млад човек и заслужава да бъде запечатан безупречно.</b>
                </h2>

                <div class="section-divider"></div>
                <p class="text-black mb-0">
                    В <b>Take Two Studio 1603</b> предлагаме пълно фотографско покритие на абитуриентски балове, от първата усмивка до последния танц.<br>От фотосесиите през каненето и изпращането, до върха на нощта, самия бал, ще създадем спомени, които ще живеят вечно.
                </p>
            </div>
        </div>
    </section>

    <!-- SERVICES DETAILS -->
    <section class="pt-4 pb-2 bg-gray-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0 text-center text-lg-start service-list">
                    <h2 class="mb-4 text-center">Какво предлагаме<br> за твоя бал</h2>
                    <div class="section-divider start mx-auto"></div>
                    <p><b>Фотосесии:</b> Креативни и модерни снимки в есенна, пролетна или студийна атмосфера.</p>
                    <p><b>Видеозаснемане:</b> Професионално видео от изпращането, каненето и бала - уловено с кино ефект.</p>
                    <p><b>Дрон кадри:</b> Перспектива от въздуха за още по-зашеметяващи моменти.</p>
                    <p><b>Онлайн галерия + флашка:</b> Получаваш снимките си дигитално, за да ги споделяш с приятели и семейство.</p>
                    <p><b>Албуми:</b> Класически или персонализирани фотокниги - запази спомените завинаги.</p>
                </div>
                <div class="col-lg-6">
                    <img src="{{ asset('css/img/prom.jpg') }}" class="w-100 rounded shadow-sm" alt="Абитуриентска Фотосесия">
                </div>
            </div>
        </div>
    </section>

    <!-- WHY US -->
    <section class="py-5 bg-white" id="why-choose-us">
        <div class="container">
            <h2 class="text-center mb-5">Защо да избереш нас?</h2>
            <div class="section-divider start mx-auto mb-5 mt-0"></div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="reason-card">
                        <i class="fas fa-graduation-cap reason-icon"></i>
                        <h5>Опит с абитуриенти</h5>
                        <p>Всяка година работим с десетки випуски. Знаем точно кога и как да уловим най-доброто от абитуриентския бал, каненето и изпращането.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="reason-card">
                        <i class="fas fa-film reason-icon"></i>
                        <h5>Кино визия</h5>
                        <p>Използваме професионални камери, дрон и модерна обработка, за да направим абитуриентското ви видео като от филм.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="reason-card">
                        <i class="fas fa-user-friends reason-icon"></i>
                        <h5>Персонален подход</h5>
                        <p>Ние слушаме твоите идеи, от мястото за абитуриентска фотосесия до стила на снимките. Всичко е съобразено с теб.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="reason-card">
                        <i class="fas fa-tag reason-icon"></i>
                        <h5>Изгодни пакети</h5>
                        <p>Имаме готови пакети за абитуриентско заснемане с фиксирани цени, които покриват всичко, без скрити такси и изненади.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PORTFOLIO -->
    <section class="py-5 bg-white" id="portfolio">
        <div class="container">
            <h2 class="text-center mb-5">Нашето Портфолио</h2>
            <div class="section-divider"></div>

            @php $galleryLimit = 10; @endphp
            <div class="masonry" id="promsGallery" data-aos="fade-up">
                @foreach($promPortfolioPhotos as $i => $photo)
                <div class="masonry-item gallery-item @if($i >= $galleryLimit) gallery-hidden @endif">
                    <div class="portfolio-item">
                        <a href="{{ Storage::url($photo->image_path) }}" class="glightbox">
                            <img loading="lazy" src="{{ Storage::url($photo->image_path) }}" class="portfolio-img" alt="Абитуриентски Бал Варна">
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @if($promPortfolioPhotos->count() > $galleryLimit)
            <div class="text-center mt-4">
                <button type="button" class="btn-custom" id="loadMoreBtn" onclick="loadMorePhotos()">Виж още</button>
            </div>
            @endif
        </div>
    </section>

    <!-- CALCULATOR -->
    <section class="calc-section" id="calculator">
        <div class="container">
            <h2 class="text-center mb-4">{{ $calcTitle }}</h2>
            <p class="text-center mb-5" style="max-width: 800px; margin: 0 auto 3rem auto;">
                Планирайте бюджета за вашия бал с нашия интерактивен калкулатор. Изберете фотосесия, видео заснемане на
                изпращането и ресторанта, както и допълнителни екстри като дрон и видео визитки. Запазете спомените от
                най-емоционалната вечер!
            </p>
            @if(session('success'))
                <div class="alert alert-success text-center mb-4">
                    {{ session('success') }}
                </div>
            @endif
            <form id="promCalcForm" action="{{ url('/submit-order') }}" method="post">
                @csrf
                <div class="row g-5">

                    <!-- LEFT: CONTROLS -->
                    <div class="col-lg-8">
                        <div class="calc-card h-100">

                            <!-- 1. PACKAGES -->
                            <h4 class="mb-4 text-center"><i class="fas fa-crown me-2 text-warning"></i> Избери Пакет
                            </h4>

                            <div class="row g-4 mb-5 justify-content-center">
                                @foreach($service->packages as $index => $package)
                                <div class="col-md-6">
                                    <input type="radio" name="prom_package" id="pkg_{{ $package->id }}" class="package-option"
                                        value="{{ (int)$package->price_eur }}" data-label="{{ $package->name_bg }}" {{ $package->is_default ? 'checked' : '' }} onchange="calculatePromTotal()">
                                    <label for="pkg_{{ $package->id }}" class="package-label {{ Str::contains(Str::lower($package->name_bg), 'лукс') ? 'lux-pack' : '' }}">
                                        <i class="fas {{ $package->icon_class ?? 'fa-star' }} package-icon"></i>
                                        <strong>{{ $package->name_bg }}</strong>
                                        <span class="price-tag">€ {{ number_format($package->price_eur, 0) }} / {{ number_format($package->price_eur * 1.9558, 2) }} лв. / ученик</span>
                                        @if($package->description_bg)
                                        <!-- <div class="section-divider mt-4 mb-3" style="width: 80%; opacity: 0.1;"></div> -->
                                        <ul class="package-details w-100 px-3">
                                            @php
                                                $desc = $package->description_bg;
                                                $hasHtml = strip_tags($desc) !== $desc;
                                            @endphp
                                            @if($hasHtml)
                                                {!! $desc !!}
                                            @else
                                                @foreach(explode("\n", $desc) as $line)
                                                    @php 
                                                        $text = trim($line);
                                                        $isBold = Str::contains(Str::lower($text), ['2 фотосесии', 'флашка']);
                                                    @endphp
                                                    @if($text)
                                                        <li><i class="fas fa-check small"></i> @if($isBold)<b>{{ $text }}</b>@else{{ $text }}@endif</li>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </ul>
                                        @endif
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <!-- 2. ALBUMS & EXTRAS -->
                            <h4 class="mb-4 text-center"><i class="fas fa-book-open me-2 text-warning"></i> Добавки</h4>
                            <div class="row g-3 justify-content-center">
                                @php
                                    $allExtras = $service->extras->sortBy('display_order');
                                @endphp
                                @foreach($allExtras as $extra)
                                    @php
                                        // Specific grid matching reference exactly
                                        $colClass = 'col-6 col-md-4';
                                        if (str_contains(strtolower($extra->label_bg), 'студийна') || str_contains(strtolower($extra->label_bg), 'флашка')) {
                                            $colClass = 'col-6 col-md-6';
                                        }
                                    @endphp
                                    <div class="{{ $colClass }}">
                                        <input class="extra-option" type="{{ $extra->input_type }}" name="{{ $extra->input_type === 'radio' ? 'extra_group_' . Str::slug($extra->group_name_bg) : 'extra_' . $extra->id }}" 
                                            id="extra_{{ $extra->id }}" value="{{ (int)$extra->price_eur }}" data-label="{{ $extra->label_bg }}" 
                                            {{ $extra->is_default ? 'checked' : '' }} onchange="calculatePromTotal()">
                                        <label class="extra-card-label" for="extra_{{ $extra->id }}">
                                            <i class="fas {{ $extra->icon_class ?? 'fa-plus-circle' }} extra-card-icon mb-2"></i>
                                            <span class="fw-bold">{{ $extra->label_bg }}</span>
                                            <span class="extra-price mt-1">@if($extra->price_eur > 0)+€ {{ number_format($extra->price_eur, 0) }} / {{ number_format($extra->price_eur * 1.9558, 2) }} лв. @else Стандарт @endif</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT: SUMMARY -->
                    <div class="col-lg-4">
                        <div class="total-price-box">
                            <h5 class="text-uppercase text-white-50">Цена на ученик</h5>
                            <div class="price-display">€ <span id="finalPrice">103</span> / <span id="finalPriceBgn">201.45</span> лв.</div>
                            <div class="section-divider" style="background: #444; width: 100%;"></div>

                            <div class="text-start mt-4 mb-4">
                                <div class="summary-item"><span>Пакет:</span> <span id="sumPackage">Парти</span></div>
                                <div class="summary-item"><span>Добавки:</span> <span id="sumExtras">—</span></div>
                            </div>

                            <input type="hidden" id="hiddenPrice" name="final_price">
                            <input type="hidden" id="hiddenDetails" name="details">
                            <input type="hidden" name="orderType" value="Prom">

                            <div class="mt-4">
                                <input type="text" name="name" class="form-control mb-2 rounded-0"
                                    placeholder="Твоето име" required>
                                <input type="text" name="phone" class="form-control mb-2 rounded-0"
                                    placeholder="Телефон" required>
                                <input type="text" name="school" class="form-control mb-2 rounded-0"
                                    placeholder="Училище / Клас" required>
                                @include('partials.gdpr-consent', ['consentId' => 'prom'])
                                <button type="submit" class="btn btn-custom">Изпрати Запитване</button>
                            </div>

                            <p class="price-note mt-3 mb-0">
                                * 50% капаро при резервация. Остатъкът до изпращането (05.2027г.)
                            </p>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-5 bg-white">
        <div class="container">
            <h2 class="text-center mb-5">Често Задавани Въпроси</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if($promFaqs->isNotEmpty())
                    <div class="accordion" id="promFaqAccordion">
                        @foreach($promFaqs as $i => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#pfaq{{ $faq->id }}">
                                    {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="pfaq{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#promFaqAccordion">
                                <div class="accordion-body text-muted">
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

    <!-- CONTACT -->
    <section id="contact" class="py-5 bg-light text-center">
        <div class="container">
            <h2 class="pb-3">Свържи се с нас</h2>
            <div class="section-divider"></div>
            <h4>Запази своето място още сега!</h4>
            <div class="mt-4">
                <h5 class="mb-2"><strong>Instagram:</strong> <a href="https://instagram.com/taketwostudio1603" target="_blank" class="text-dark text-decoration-none hover-gold">@taketwostudio1603</a></h5>
                <h5><strong>Телефон:</strong> <a href="tel:+359886190124" class="text-dark text-decoration-none hover-gold">088 619 0124</a></h5>
            </div>
        </div>
    </section>
@endsection

@push('schema')
@php
$promServiceSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => 'Заснемане на абитуриентски балове',
    'provider' => [
        '@type' => 'LocalBusiness',
        'name' => 'Take Two Studio 1603',
        '@id' => 'https://taketwostudio1603.com',
    ],
    'areaServed' => ['@type' => 'City', 'name' => 'Варна', 'addressCountry' => 'BG'],
    'description' => 'Професионално заснемане на абитуриентски балове — фотосесии, 4K видео, дрон кадри и онлайн галерия.',
    'url' => 'https://taketwostudio1603.com/proms',
];
@endphp
<script type="application/ld+json">{!! json_encode($promServiceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@if($promFaqs->isNotEmpty())
@php
$promFaqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $promFaqs->map(fn($faq) => [
        '@type' => 'Question',
        'name' => $faq->question,
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
    ])->values()->toArray(),
];
@endphp
<script type="application/ld+json">{!! json_encode($promFaqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script src="{{ asset('js/calculators/prom.js') }}"></script>
    <script>
        const lightbox = GLightbox({
            selector: '.glightbox'
        });

        function loadMorePhotos() {
            var hidden = document.querySelectorAll('#promsGallery .gallery-hidden');
            var step = {{ $galleryLimit }};

            for (var i = 0; i < step && i < hidden.length; i++) {
                hidden[i].classList.remove('gallery-hidden');
            }

            if (document.querySelectorAll('#promsGallery .gallery-hidden').length === 0) {
                document.getElementById('loadMoreBtn').style.display = 'none';
            }

            GLightbox({ selector: '.glightbox' });
        }
    </script>
@endpush

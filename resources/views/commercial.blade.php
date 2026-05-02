@extends('layouts.app')

@section('title', 'Рекламна и Продуктова Фотография Варна | Корпоративно Видео | Take Two Studio 1603')
@section('meta_description', 'Увеличете продажбите си с професионална рекламна фотография и видеозаснемане. Продуктова фотография за онлайн магазини, корпоративни събития, дрон услуги и съдържание за социални мрежи във Варна.')
@section('meta_keywords', 'продуктова фотография варна, рекламно видео, заснемане на хотел, бизнес портрети, корпоративно събитие, дрон услуги, снимки за онлайн магазин, Take Two Studio')
@section('og_title', 'Професионално Фото и Видео за Вашия Бизнес | Take Two Studio')
@section('og_description', 'Качественото визуално съдържание продава. Доверете се на нас за вашата рекламна визия, продуктови кадри и видео презентации.')
@section('og_image', asset('css/img/commercial-portfolio-cover.jpg'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/commercial.css') }}">
@endpush

@section('content')

    @php
        $pageContent = \App\Models\PageContent::where('page_slug', 'commercial')->get();
        $heroTitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'title')->first()?->content_bg ?? 'Реклама и Бизнес';
        $heroSubtitle = $pageContent->where('section_slug', 'hero')->where('field_key', 'subtitle')->first()?->content_bg ?? 'Визуална идентичност, която продава';
    @endphp

    <!-- Header / Hero -->
    <section class="commercial-hero" @if(!empty($service->hero_image)) style="background-image: url('{{ asset('storage/' . $service->hero_image) }}')" @endif>
        <div class="hero-overlay"></div>
        <div class="hero-title" data-aos="fade-up">
            <h1>{{ $heroTitle }}</h1>
            <p>{{ $heroSubtitle }}</p>
        </div>
    </section>

    <!-- SEO Intro -->
    <section class="py-5 text-center container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h3 class="mb-4 h4 text-muted fw-normal lh-base">
                    В дигиталната ера визуалното съдържание е лицето на вашия бизнес.
                </h3>
                <div class="section-divider"></div>
                <p class="text-muted mb-4">
                    В <b>Take Two Studio 1603</b> създаваме висококачествено фото и видео съдържание за брандове, които
                    искат да се отличат.
                </p>
            </div>
        </div>
    </section>

    <!-- Services Grid -->
    <section id="services" class="py-5 bg-gray-light">
        <div class="container">
            <h2 class="text-center mb-5">Корпоративни Услуги</h2>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="service-card"><i class="fas fa-video service-icon"></i>
                        <h5>Рекламни Клипове</h5>
                        <p>Динамични видеа за социалните мрежи.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card"><i class="fas fa-box-open service-icon"></i>
                        <h5>Продуктова Фотография</h5>
                        <p>Качествени кадри за вашия каталог.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card"><i class="fas fa-briefcase service-icon"></i>
                        <h5>Корпоративни Събития</h5>
                        <p>Репортажно заснемане на събития.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="service-card"><i class="fas fa-helicopter service-icon"></i>
                        <h5>Дрон Заснемане</h5>
                        <p>Впечатляващи въздушни кадри.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card"><i class="fas fa-id-card service-icon"></i>
                        <h5>Видео Визитки</h5>
                        <p>Кратко представяне на вашия бизнес.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card"><i class="fas fa-camera-retro service-icon"></i>
                        <h5>Имиджови Сесии</h5>
                        <p>Бизнес портрети и интериор.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filterable Portfolio Gallery -->
    <section class="py-5 bg-white" id="portfolio">
        <div class="container">
            <h2 class="text-center mb-4">Нашият почерк</h2>
            <div class="section-divider"></div>

            <div class="portfolio-filters" data-aos="fade-up">
                <button class="filter-btn active" data-filter="all">Всички</button>
                <button class="filter-btn" data-filter="ads">Реклама</button>
                <button class="filter-btn" data-filter="product">Продуктова</button>
                <button class="filter-btn" data-filter="imoti">Имоти</button>
                <button class="filter-btn" data-filter="events">Събития</button>
                <button class="filter-btn" data-filter="drone">Дрон</button>
            </div>

            <div class="masonry" data-aos="fade-up">
                @foreach($commercialPhotos as $item)
                    @php
                        $categoryMap = [
                            'ads'     => 'Рекламна Фотография',
                            'product' => 'Продуктова Фотография',
                            'imoti'   => 'Интериорна фотография',
                            'events'  => 'Събития',
                            'drone'   => 'Въздушни Кадри',
                        ];
                        $displayCategory = $categoryMap[$item->sub_category] ?? $item->sub_category;
                    @endphp
                    <div class="masonry-item {{ $item->sub_category }}">
                        <div class="portfolio-item" onclick="openLightbox(this)">
                            <img src="{{ str_starts_with($item->image_path, 'css/') ? asset($item->image_path) : Storage::url($item->image_path) }}"
                                class="portfolio-img" alt="{{ $item->alt_text ?? $displayCategory }}" loading="lazy">
                            <div class="portfolio-overlay">
                                <div class="portfolio-info">
                                    @if($item->alt_text)
                                        <h4 class="portfolio-title">{{ $item->alt_text }}</h4>
                                    @endif
                                    <span class="portfolio-subtitle">{{ $displayCategory }}</span>
                                    @if($item->description)
                                        <div class="portfolio-desc-wrapper">
                                            <p class="portfolio-desc">{{ $item->description }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ str_starts_with($item->image_path, 'css/') ? asset($item->image_path) : Storage::url($item->image_path) }}" class="glightbox d-none"
                               data-title="{{ $item->alt_text ?? $displayCategory }}"
                               data-description="{{ $item->description ?? '' }}"></a>
                        </div>
                    </div>
                @endforeach

                @if($commercialPhotos->isEmpty())
                    <div class="col-12 text-center text-muted">
                        <p>Очаквайте скоро нашите нови кадри!</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @php
    $commercialFaqs = [
        ['q' => 'Каква е цената за продуктова фотография?', 'a' => 'Цената зависи от броя продукти, вида обработка и специфичните изисквания на проекта. Изпратете ни запитване с детайли и ще изготвим индивидуална оферта за вас.'],
        ['q' => 'Правите ли рекламни видеа за социалните мрежи?', 'a' => 'Да, създаваме кратко и динамично видео съдържание, оптимизирано за Instagram, Facebook, TikTok и YouTube. Работим с дрон, студийно осветление и модерна постпродукция.'],
        ['q' => 'Снимате ли хотели, ресторанти и имоти?', 'a' => 'Да, предлагаме интериорна и екстериорна фотография за хотели, ресторанти, фитнес зали и имоти. Дроните ни добавят въздушна перспектива, която се откроява.'],
        ['q' => 'Работите ли с малък и среден бизнес?', 'a' => 'Разбира се! Работим с бизнеси от всякакъв мащаб — от малки онлайн магазини до корпоративни компании. Всеки проект получава индивидуално внимание и подход.'],
    ];
    @endphp

    <!-- FAQ -->
    <section class="py-5 bg-white">
        <div class="container">
            <h2 class="text-center mb-5">Често Задавани Въпроси</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="commercialFaqAccordion">
                        @foreach($commercialFaqs as $i => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#cfaq{{ $i }}">
                                    {{ $faq['q'] }}
                                </button>
                            </h2>
                            <div id="cfaq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#commercialFaqAccordion">
                                <div class="accordion-body text-muted">
                                    {{ $faq['a'] }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Inquiry Form -->
    <section class="py-5 bg-light" id="contact">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-box">
                        <h2 class="text-center mb-4">Поискайте Оферта</h2>
                        <p class="text-center text-muted mb-5">
                            Всеки бизнес проект е уникален. Опишете ни вашите нужди, а ние ще изготвим индивидуално
                            предложение за вас.
                        </p>

                    @if(session('success'))
                        <div class="alert alert-success mb-4 text-center">
                            {{ session('success') }}
                        </div>
                    @endif
                        <form action="{{ url('/submit-order') }}" method="post">
                            @csrf
                            <input type="hidden" name="orderType" value="Commercial">

                            @include('partials.promo-code-input')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Име / Фирма</label>
                                    <input type="text" class="form-control rounded-0 p-3 bg-light border-0" name="name"
                                        required placeholder="Вашето име">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Телефон</label>
                                    <input type="text" class="form-control rounded-0 p-3 bg-light border-0" name="phone"
                                        required placeholder="Телефон за връзка">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Имейл</label>
                                    <input type="email" class="form-control rounded-0 p-3 bg-light border-0" name="email"
                                        required placeholder="Email адрес">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Описание на проекта</label>
                                    <textarea class="form-control rounded-0 p-3 bg-light border-0" rows="5" name="message"
                                        placeholder="Какъв тип заснемане ви трябва? Дата, локация, специфични изисквания..."></textarea>
                                </div>
                                <div class="col-12">
                                    @include('partials.gdpr-consent', ['consentId' => 'commercial'])
                                </div>
                                <div class="col-12 text-center mt-4">
                                    <button type="submit" class="btn btn-submit px-5 py-3 fs-5">Изпрати
                                        Запитване</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('schema')
@php
$commercialServiceSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => 'Рекламна и продуктова фотография',
    'provider' => [
        '@type' => 'LocalBusiness',
        'name' => 'Take Two Studio 1603',
        '@id' => 'https://taketwostudio1603.com',
    ],
    'areaServed' => ['@type' => 'City', 'name' => 'Варна', 'addressCountry' => 'BG'],
    'description' => 'Рекламна фотография, продуктови кадри, корпоративни видеа и дрон заснемане за бизнеси във Варна и цяла България.',
    'url' => 'https://taketwostudio1603.com/commercial',
];
$commercialFaqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($faq) => [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
    ], $commercialFaqs),
];
@endphp
<script type="application/ld+json">{!! json_encode($commercialServiceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($commercialFaqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script src="{{ asset('js/commercial.js') }}"></script>
    <script>
        const lightbox = GLightbox({
            selector: '.glightbox'
        });
    </script>
@endpush
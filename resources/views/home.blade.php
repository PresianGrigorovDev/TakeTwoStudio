@extends('layouts.app')

@section('title', 'Фотограф и Видеозаснемане Варна | Take Two Studio 1603')
@section('meta_description', 'Професионален фотограф и видеооператор във Варна от Take Two Studio 1603. Сватби, балове, събития, портрети, семейна и дрон фотография с 4K качество.')
@section('meta_keywords', 'фотограф варна, сватбен фотограф варна, фотограф за бал варна, заснемане на събития варна, семейна фотосесия, портретен фотограф, автомобилна фотография, архитектурна фотография, видеозаснемане варна, дрон заснемане, Take Two Studio 1603')
@section('og_title', 'Take Two Studio 1603 - Фотограф и Видеозаснемане Варна')
@section('og_description', 'Професионален фотограф и видеооператор във Варна от Take Two Studio 1603. Сватби, балове, събития, портрети, семейна и дрон фотография.')
@section('og_image', asset('css/img/social-share-cover.jpg'))

@section('preload')
    <link rel="preload" href="{{ asset('css/img/header.webp') }}" as="image" type="image/webp" fetchpriority="high">
@endsection

@section('content')

    <header class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content" data-aos="fade-up">
            <h1>Фотограф и Видеозаснемане Варна</h1>
            <span class="hero-subtitle">Сватби, абитуриентски балове, кръщенета, семейни и събитийни фотосесии във Варна и
                цяла България</span>

            <!-- Desktop Buttons -->
            <div class="d-none d-md-flex flex-wrap justify-content-center gap-2 mt-4">
                <a href="#portfolio" class="btn-custom-full">Портфолио</a>
                <a href="#contact" class="btn-custom">Свържи се с нас</a>
            </div>

            <!-- Mobile Buttons -->
            <div class="d-flex d-md-none flex-wrap justify-content-center gap-2 mt-4">
                <a href="#portfolio" class="btn-custom-full">Услуги</a>
                <button type="button" class="btn-custom" data-bs-toggle="modal" data-bs-target="#quickLeadModal"
                    style="background: var(--accent, #d4af37); color: #000; font-weight: 700; border: none;">Бърза
                    Оферта</button>
            </div>
        </div>
    </header>

    <section id="about" class="py-5">
        <div class="container py-5">
            <div class="row align-items-center g-5 mb-5" data-aos="fade-up">
                <div class="col-lg-6">
                    <x-picture :src="asset('css/img/about.webp')" alt="Екипът на Take Two"
                        class="about-img-main falling-item" />
                </div>
                <div class="col-lg-6 text-center text-lg-start">
                    <h2 class="mb-3">Професионално фото и видео студио</h2>
                    <div class="section-divider start"></div>

                    <p class="text-muted mb-4">
                        Търсите ли качество и емоция? В <b>Take Two Studio 1603</b> сме специализирани в
                        <b>професионална сватбена фотография</b> и <b>видеозаснемане</b>.
                        Базирани във <b>Варна</b>, ние отразяваме вашите най-специални поводи -
                        от сватби и <b>абитуриентски балове</b> до свето <b>кръщение</b> и корпоративни събития.
                    </p>
                    <p class="text-muted">
                        С над десетилетие опит и стотици доволни клиенти в цялата страна, нашата мисия е да запечатаме
                        емоциите ви в кадри с кинематографично качество.
                    </p>
                </div>
            </div>

            <div class="row g-4 justify-content-center text-center" data-aos="fade-up">
                @foreach($teamMembers as $member)
                    <div class="col-md-4 col-sm-6 team-member" data-bs-toggle="modal"
                        data-bs-target="#modalTeam{{ $member->id }}" title="Кликнете за повече инфо">
                        <x-picture :src="$member->image_path ? (str_starts_with($member->image_path, 'http') ? $member->image_path : asset('storage/' . $member->image_path)) : asset('css/img/default-avatar.png')" alt="{{ $member->name }}" class="team-img falling-item" />
                        <h3 class="fw-bold mb-1 h5">{{ $member->name }}</h3>
                        <p class="role-text">{{ $member->role_bg }}</p>
                        <small class="text-muted d-block mt-2" style="font-size: 0.75rem;"><i
                                class="fas fa-plus-circle me-1"></i> Прочети повече</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="brands-section">
        <div class="container mb-4">
            <div class="brands-header">
                <h2 class="text-center">Довериха ни се</h2>
                <div class="section-divider"></div>
                <p class="text-muted text-uppercase text-center" style="letter-spacing: 2px;">Партньори и Клиенти</p>
            </div>
        </div>
        <div class="brands-track">
            @foreach($partners as $partner)
                <x-picture :src="str_starts_with($partner->logo_path, 'http') ? $partner->logo_path : asset('storage/' . $partner->logo_path)" class="brand-logo" alt="{{ $partner->name }}" />
            @endforeach

            <!-- Duplicated for scrolling effect -->
            @foreach($partners as $partner)
                <x-picture :src="str_starts_with($partner->logo_path, 'http') ? $partner->logo_path : asset('storage/' . $partner->logo_path)" class="brand-logo" alt="{{ $partner->name }}" />
            @endforeach
        </div>
    </section>

    <section class="py-5 px-1 bg-white" data-aos="fade-up">
        <div class="container pb-5 text-center">
            <h2 class="mb-3">Как работим</h2>
            <div class="section-divider"></div>
            <p class="text-muted mb-5" style="max-width: 700px; margin: 0 auto;">
                Вярваме, че добрата фотография започва далеч преди натискането на бутона.
            </p>

            <div class="row g-4 g-md-5 process-steps-mobile-slider">
                <div class="col-md-4 process-step-col">
                    <div class="process-step falling-item h-100">
                        <div class="process-icon-wrap"><i class="far fa-comments"></i></div>
                        <h3 class="fw-bold h5 mt-3">Опознаване</h3>
                        <p class="text-muted small mb-0">Преди камерата да заработи, искаме да чуем вашата история.</p>
                    </div>
                </div>
                <div class="col-md-4 process-step-col">
                    <div class="process-step falling-item h-100">
                        <div class="process-icon-wrap"><i class="fas fa-camera-retro"></i></div>
                        <h3 class="fw-bold h5 mt-3">В деня на събитието</h3>
                        <p class="text-muted small mb-0">Улавяме истинските емоции и спонтанни моменти.</p>
                    </div>
                </div>
                <div class="col-md-4 process-step-col">
                    <div class="process-step falling-item h-100">
                        <div class="process-icon-wrap"><i class="fas fa-magic"></i></div>
                        <h3 class="fw-bold h5 mt-3">Вечният спомен</h3>
                        <p class="text-muted small mb-0">Предаваме ви не просто файлове, а цялостна история.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="portfolio" class="pb-5">
        <div class="container pb-5">
            <div class="text-center mb-5 portfolio-header">
                <h2>Портфолио</h2>
                <div class="section-divider"></div>
                <p>Разгледайте избрани моменти от нашите проекти.</p>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach($portfolioCategories as $index => $category)
                    @if ($category->is_visible)
                        <div class="col-md-6" data-aos="fade-up" {!! $index % 2 != 0 ? 'data-aos-delay="100"' : '' !!}>
                            <a href="{{ url($category->slug) }}" class="text-decoration-none">
                                <div class="portfolio-item falling-item">
                                    <x-picture :src="$category->cover_image ? (str_starts_with($category->cover_image, 'http') ? $category->cover_image : asset('storage/' . $category->cover_image)) : asset('css/img/default-placeholder.jpg')" class="portfolio-img"
                                        alt="{{ $category->name_bg }}" />
                                    <div class="portfolio-overlay">
                                        <div class="portfolio-info">
                                            <h3 class="portfolio-title">{{ $category->name_bg }}</h3>
                                            <p class="portfolio-subtitle">{{ $category->subtitle_bg }}</p>
                                        </div>
                                        <div class="view-more-btn">Виж Още</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5 px-1 bg-light">
        <div class="container text-center">
            <h2 class="mb-3">Какво казват клиентите</h2>
            <div class="section-divider"></div>
            <div class="row justify-content-center mt-5 testimonials-mobile-slider">
                @foreach($testimonials as $index => $testimonial)
                    <div class="col-md-4 testimonial-col" data-aos="fade-up" data-aos-delay="{{ $index * 100 }}">
                        <div class="testimonial-card falling-item h-100">
                            <i class="fas fa-quote-left quote-icon"></i>
                            <div class="testimonial-stars text-warning mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fa-star {{ $i <= ($testimonial->rating ?? 5) ? 'fas' : 'far' }}"></i>
                                @endfor
                            </div>
                            <p>"{{ $testimonial->content_bg }}"</p>
                            <div class="mt-3 fw-bold h6">- {{ $testimonial->client_name_bg ?? $testimonial->client_name }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="services" class="py-5" aria-label="Фотографски услуги във Варна">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2>Нашите Услуги</h2>
                <div class="section-divider"></div>
                <p class="text-muted">Професионална фотография и видеозаснемане за всеки повод - сватби, балове, събития,
                    портрети и още</p>
            </div>

            <div class="row g-4 services-mobile-slider">
                @foreach($services as $index => $service)
                    <div class="col-md-4 col-sm-6 service-card-col" data-aos="fade-up" {!! $index % 3 == 1 ? 'data-aos-delay="100"' : ($index % 3 == 2 ? 'data-aos-delay="200"' : '') !!}>
                        <a href="{{ url($service->slug) }}" class="text-decoration-none text-dark h-100 d-block">
                            <div class="service-card bg-white {{ $service->slug }} falling-item h-100">
                                <i class="{{ $service->icon_class ?? 'fas fa-star' }} service-icon"></i>
                                <h3 class="fw-bold h5">{{ $service->name_bg }}</h3>
                                <p class="small text-muted mb-0">{{ $service->description_bg }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="contact" class="py-5 bg-light">
        <div class="container py-5 text-center">
            <h2 class="mb-3">Свържете се с нас</h2>
            <div class="section-divider"></div>

            <div class="row justify-content-center mb-5 g-4 mt-2">
                <div class="col-md-4"><i class="fas fa-phone mb-3 text-black fa-2x"></i>
                    <div class="fs-6 fw-bold"><a class="text-black text-decoration-none"
                            href="tel:{{ \App\Support\Settings::phoneHref(\App\Support\Settings::phone()) }}">{{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}</a>
                    </div>
                </div>
                <div class="col-md-4"><i class="far fa-envelope mb-3 text-black fa-2x"></i>
                    <div class="fs-6 fw-bold"><a class="text-black text-decoration-none"
                            href="mailto:{{ \App\Support\Settings::email() }}">{{ \App\Support\Settings::email() }}</a>
                    </div>
                </div>
                <div class="col-md-4"><i class="fas fa-map-marker-alt mb-3 text-black fa-2x"></i>
                    <div class="fs-6 fw-bold">ж.к. Възраждане IV 1603, Варна</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    @if(session('success'))
                        <div class="alert alert-success mb-4 text-center">
                            {{ session('success') }}
                        </div>
                    @endif
                    <form action="{{ url('/submit-contact') }}" method="post"
                        class="h-100 p-4 border rounded shadow-sm bg-white">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6"><input type="text" class="form-control rounded-0 p-3 bg-light border-0"
                                    placeholder="Име" name="name" required></div>
                            <div class="col-md-6"><input type="text" class="form-control rounded-0 p-3 bg-light border-0"
                                    placeholder="Телефон" name="phone" required></div>
                            <div class="col-12"><input type="email" class="form-control rounded-0 p-3 bg-light border-0"
                                    placeholder="Email" name="email" required></div>
                            <div class="col-12"><textarea class="form-control rounded-0 p-3 bg-light border-0" rows="5"
                                    placeholder="Съобщение..." name="message"></textarea></div>
                            <div class="col-12"><button type="submit" class="btn-submit mt-2">Изпрати запитване</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-6">
                    @include('partials.map-embed')
                </div>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <div id="lightbox" class="lightbox" onclick="this.style.display='none'">
        <span class="position-absolute top-0 end-0 m-4 text-white fs-1 cursor-pointer">×</span>
        <img id="lightbox-img" src="" style="max-height: 90vh; max-width: 90vw;" loading="lazy" decoding="async">
    </div>

    @foreach($teamMembers as $member)
        <div class="modal fade" id="modalTeam{{ $member->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content custom-modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">{{ $member->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <x-picture :src="$member->image_path ? (str_starts_with($member->image_path, 'http') ? $member->image_path : asset('storage/' . $member->image_path)) : asset('css/img/default-avatar.png')" alt="{{ $member->name }}" class="team-img mb-3"
                            style="width: 120px; height: 120px;" />
                        <p class="role-text mb-3">{{ $member->role_bg }}</p>
                        <p class="text-muted mb-3">
                            {{ $member->bio_bg }}
                        </p>
                        @if($member->phone || $member->instagram_url)
                            <div class="mt-3 pt-3 border-top d-flex justify-content-center gap-3 flex-wrap">
                                @if($member->phone)
                                    <a href="tel:{{ $member->phone }}" class="btn btn-outline-dark btn-sm rounded-0">
                                        <i class="fas fa-phone me-1"></i> {{ $member->phone }}
                                    </a>
                                @endif
                                @if($member->instagram_url)
                                    <a href="{{ $member->instagram_url }}" target="_blank"
                                        class="btn btn-outline-dark btn-sm rounded-0">
                                        <i class="fab fa-instagram me-1"></i> Instagram
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
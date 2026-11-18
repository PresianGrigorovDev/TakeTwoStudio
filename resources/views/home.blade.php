@extends('layouts.app')

@section('title', 'Фотограф и Видеозаснемане Варна | Take Two Studio 1603')
@section('meta_description', 'Професионален фотограф и видеооператор във Варна от Take Two Studio 1603. Сватби, балове, събития, портрети, семейна и дрон фотография с 4K качество.')
@section('meta_keywords', 'фотограф варна, сватбен фотограф варна, фотограф за бал варна, заснемане на събития варна, семейна фотосесия, портретен фотограф, автомобилна фотография, архитектурна фотография, видеозаснемане варна, дрон заснемане, Take Two Studio 1603')
@section('og_title', 'Take Two Studio 1603 - Фотограф и Видеозаснемане Варна')
@section('og_description', 'Професионален фотограф и видеооператор във Варна от Take Two Studio 1603. Сватби, балове, събития, портрети, семейна и дрон фотография.')
@section('og_image', asset('css/img/social-share-cover.jpg'))

@section('home-navigation', '1')

@section('preload')
    <link rel="preload" href="{{ asset('css/img/prom.webp') }}" as="image" type="image/webp" fetchpriority="high">
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\Assets::versioned('css/home.css') }}">
@endpush

@section('content')
    @php
        $businessInquiry = 'Реклама и бизнес';
        $inquiryServices = [
            'Абитуриентски бал за целия клас',
            'Предбална фотосесия',
            'Сватба',
            $businessInquiry,
            ...$otherServices->pluck('name_bg')->all(),
            'Друго',
        ];
        $inquiryErrors = $errors->getBag('homeInquiry');
    @endphp

    <div class="home-page">
        <header class="home-hero home-wrap">
            <div class="home-hero-copy">
                <h1>Фотография и видео във Варна</h1>
                <p>Един екип за снимките и филма на вашия бал, сватба или събитие.</p>
                <div class="home-actions">
                    <a class="home-button" href="#contact">Изпрати запитване</a>
                    <a class="home-link" href="#portfolio">Виж нашата работа</a>
                </div>
            </div>
            <img class="home-hero-photo" src="{{ asset('css/img/prom.webp') }}"
                alt="Усмихнат абитуриент вдига ръце сред музиканти и гости" fetchpriority="high" decoding="async">
        </header>

        <section id="services" class="home-wrap home-section">
            <h2>Какво снимаме</h2>
            <div class="home-service-pair">
                <article class="home-service">
                    <a href="{{ url('/proms') }}#calculator">
                        <x-picture :src="asset('css/img/proms-hero.webp')" alt="" class="home-service-photo" />
                        <div class="home-card-copy">
                            <h3>Абитуриентски балове</h3>
                            <p>Фото и видео за целия клас</p>
                            <span class="home-link">Вижте пакетите за класа</span>
                        </div>
                    </a>
                </article>
                <article class="home-service">
                    <a href="{{ $featuredStory ? $featuredStory->url : url('/weddings') }}">
                        <x-picture :src="asset('css/img/best-wedding-cover.webp')" alt="" class="home-service-photo" />
                        <div class="home-card-copy">
                            <h3>Сватби</h3>
                            <p>Сватбена фотография и филм</p>
                            <span class="home-link">Вижте сватбени истории</span>
                        </div>
                    </a>
                </article>
            </div>
        </section>

        <section id="portfolio" class="home-section home-band">
            <div class="home-wrap">
                @if($featuredStory)
                    <div class="home-section-heading">
                        <div>
                            <h2>Сватбата на {{ $featuredStory->title }}</h2>
                            @if($featuredStory->place_label)
                                <p>{{ $featuredStory->place_label }}</p>
                            @endif
                        </div>
                        <div class="home-actions">
                            <a class="home-link" href="{{ $featuredStory->url }}">Вижте цялата история</a>
                            @if($featuredStory->video_id)
                                <a class="home-link" href="{{ $featuredStory->url }}#wedding-film">Гледайте филма</a>
                            @endif
                        </div>
                    </div>
                    @if($featuredStory->description)
                        <p class="home-story-description">{{ \Illuminate\Support\Str::limit(strip_tags($featuredStory->description), 200) }}</p>
                    @endif
                    <div class="home-story-photos">
                        @php
                            $storyPhotos = $featuredStory->photos->pluck('image_path')
                                ->prepend($featuredStory->cover_image)->filter()->unique()->take(3);
                        @endphp
                        @foreach($storyPhotos as $path)
                            <x-picture :src="\App\Support\Images::url($path)"
                                :alt="'Сватбата на ' . $featuredStory->title . ' – кадър ' . $loop->iteration" />
                        @endforeach
                    </div>
                @else
                    <div class="home-story-fallback">
                        <x-picture :src="asset('css/img/header.webp')" alt="Сватбена прегръдка с букет в преден план" />
                        <div>
                            <h2>Вижте ни в действие</h2>
                            <p>Разгледайте сватбените ни галерии и филми.</p>
                            <a class="home-link" href="{{ url('/weddings') }}">Към сватбеното портфолио</a>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section id="testimonials" class="home-wrap home-section home-trust">
            <div id="about" class="home-about">
                <x-picture :src="asset('css/img/about.webp')"
                    alt="Двама от екипа на Take Two Studio преглеждат кадър на камерата" class="about-img-main" />
                <div>
                    <h2>Екипът зад камерата</h2>
                    <p>Снимаме фото и видео като един екип. Преди събитието уточняваме кои моменти са важни за вас и как да ги заснемем.</p>
                    @if($teamMembers->isNotEmpty())
                        <p class="home-team-names">{{ $teamMembers->pluck('name')->join(', ', ' и ') }}</p>
                    @endif
                    <a class="home-link" href="{{ route('pages.about') }}">Запознайте се с нас</a>
                </div>
            </div>
            @if($testimonials->isNotEmpty())
                <div class="home-reviews">
                    <h2>Какво казват клиентите</h2>
                    @foreach($testimonials as $testimonial)
                        <blockquote>
                            <p>„{{ $testimonial->content_bg }}“</p>
                            <p class="home-review-author">{{ $testimonial->client_name_bg ?? $testimonial->client_name }}</p>
                        </blockquote>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="home-section home-band">
            <div class="home-wrap home-more">
                <div>
                    <h2>Още услуги</h2>
                    <ul class="home-service-list">
                        @foreach($otherServices as $service)
                            <li><a class="home-link" href="{{ url($service->slug) }}">{{ $service->name_bg }}</a></li>
                        @endforeach
                        <li><a class="home-link" href="{{ route('pages.prices') }}">Вижте ценоразписа</a></li>
                    </ul>
                </div>

                @if($showBusiness)
                    <article class="home-business">
                        <x-picture :src="asset('css/img/ads/subitie.webp')" alt="Говорител на сцена по време на събитие" />
                        <div class="home-card-copy">
                            <h3>Реклама и бизнес</h3>
                            <p>Продуктова фотография, рекламно видео и фирмени събития.</p>
                            <div class="home-actions">
                                <a class="home-link" href="#contact" data-inquiry-service="{{ $businessInquiry }}">Поискайте оферта</a>
                                <a class="home-link" href="{{ url('/commercial') }}#portfolio">Вижте проекти</a>
                            </div>
                        </div>
                    </article>
                @endif

                @if($partners->isNotEmpty())
                    <div class="home-partners">
                        <h3>Довериха ни се</h3>
                        <ul>
                            @foreach($partners as $partner)
                                <li><x-picture :src="\App\Support\Images::url($partner->logo_path)"
                                    :alt="$partner->name" class="brand-logo" /></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>

        <section id="contact" class="home-wrap home-section home-contact">
            <div>
                <h2>Разкажете ни за събитието</h2>
                <p>След запитването ще се свържем с вас, за да уточним датата и какво искате да заснемем.</p>
                <ol class="home-contact-steps">
                    <li>Уточняваме повода и датата.</li>
                    <li>Предлагаме подходящ пакет.</li>
                    <li>Потвърждаваме ангажимента.</li>
                </ol>
                <a class="home-link" href="tel:{{ \App\Support\Settings::phoneHref(\App\Support\Settings::phone()) }}">
                    Обадете се на {{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}
                </a>
            </div>

            <div class="home-form-panel">
                @if(session('homeInquirySuccess'))
                    <p class="home-feedback home-feedback-success" role="status" tabindex="-1">{{ session('homeInquirySuccess') }}</p>
                @endif
                @if($inquiryErrors->any())
                    <p class="home-feedback home-feedback-error" role="alert" tabindex="-1">Проверете отбелязаните полета и опитайте отново.</p>
                @endif
                <form class="home-form" action="{{ url('/submit-contact') }}" method="post">
                    @csrf
                    <input type="hidden" name="form" value="home-inquiry">
                    <div>
                        <label for="home-service">Вид услуга</label>
                        <select id="home-service" name="orderType" required
                            @error('orderType', 'homeInquiry') aria-invalid="true" aria-describedby="home-service-error" @enderror>
                            <option value="">Изберете услуга</option>
                            @foreach($inquiryServices as $serviceName)
                                <option value="{{ $serviceName }}" @selected(old('orderType') === $serviceName)>{{ $serviceName }}</option>
                            @endforeach
                        </select>
                        @error('orderType', 'homeInquiry') <p class="home-field-error" id="home-service-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="home-period">Дата или период <span>(по желание)</span></label>
                        <input id="home-period" name="event_period" type="text" maxlength="120"
                            value="{{ old('event_period') }}" placeholder="Напр. края на май"
                            @error('event_period', 'homeInquiry') aria-invalid="true" aria-describedby="home-period-error" @enderror>
                        @error('event_period', 'homeInquiry') <p class="home-field-error" id="home-period-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="home-name">Име</label>
                        <input id="home-name" name="name" type="text" autocomplete="name" maxlength="255" value="{{ old('name') }}" required
                            @error('name', 'homeInquiry') aria-invalid="true" aria-describedby="home-name-error" @enderror>
                        @error('name', 'homeInquiry') <p class="home-field-error" id="home-name-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="home-phone">Телефон</label>
                        <input id="home-phone" name="phone" type="tel" autocomplete="tel" maxlength="20" value="{{ old('phone') }}" required
                            @error('phone', 'homeInquiry') aria-invalid="true" aria-describedby="home-phone-error" @enderror>
                        @error('phone', 'homeInquiry') <p class="home-field-error" id="home-phone-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="home-form-wide">
                        <label for="home-message">Какво искате да заснемем? <span>(по желание)</span></label>
                        <textarea id="home-message" name="message" rows="3"
                            @error('message', 'homeInquiry') aria-invalid="true" aria-describedby="home-message-error" @enderror>{{ old('message') }}</textarea>
                        @error('message', 'homeInquiry') <p class="home-field-error" id="home-message-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="home-form-wide">
                        @include('partials.gdpr-consent', ['consentId' => 'home', 'consentErrorBag' => 'homeInquiry'])
                    </div>
                    <button class="home-button" type="submit">Изпрати запитване</button>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\Assets::versioned('js/home.js') }}" defer></script>
@endpush

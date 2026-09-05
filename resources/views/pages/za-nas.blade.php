@extends('layouts.app')

@section('title', 'За нас | Take Two Studio 1603 – фото и видео студио Варна')
@section('meta_description', 'Екипът на Take Two Studio 1603: фото и видео студио във Варна за сватби, балове, кръщенета и бизнес проекти. Как работим, техника, договор.')
@section('og_title', 'За нас | Take Two Studio 1603')
@section('og_description', 'Екипът, подходът и техниката зад Take Two Studio 1603 във Варна.')
@section('og_image', asset('css/img/about.webp'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <h1>{{ $text->get('hero', 'title', 'За нас: фото и видео студио във Варна') }}</h1>
            <p class="page-hero__subtitle">{{ $text->get('hero', 'subtitle', 'Take Two Studio 1603 – екип от фотограф, видеооператор и дрон пилот') }}</p>
        </div>
    </section>
    @include('partials.breadcrumbs')

    <section class="py-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <img src="{{ asset('css/img/about.webp') }}" alt="Екипът на Take Two Studio 1603 на снимачна площадка" class="about-img-main img-fluid rounded" width="800" height="600" loading="lazy">
                </div>
                <div class="col-lg-6">
                    <h2 class="mb-3">{{ $text->get('story', 'title', 'Кои сме ние') }}</h2>
                    <div class="section-divider start"></div>
                    <p class="lead answer-capsule">{{ $text->get('story', 'capsule', 'Take Two Studio 1603 е фото и видео студио във Варна. Заснемаме сватби, абитуриентски балове, кръщенета, семейни и портретни фотосесии, продукти, автомобили, интериори и събития във Варна, Добрич, Шумен, Балчик и по цялото Северно Черноморие. Работим като един екип от фотограф, видеооператор и дрон пилот, така че снимките и филмът са в единен стил и се доставят заедно.') }}</p>
                    <p class="text-muted">{{ $text->get('story', 'text', 'Името идва от квартала, в който започнахме: ж.к. Възраждане IV, блок 1603. Оттогава сме заснели стотици събития в цяла България, но Варна и региона остават нашият дом и мястото, което познаваме най-добре: залите, плажовете, светлината в различните сезони.') }}</p>
                    @if($testimonialCount > 0)
                        <p class="small text-muted mb-0">{{ $testimonialCount }} публикувани отзива от клиенти на <a href="{{ url('/#testimonials') }}">началната страница</a>.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($teamMembers->isNotEmpty())
    <section class="py-5 bg-light" id="ekip">
        <div class="container">
            <h2 class="text-center mb-3">{{ $text->get('team', 'title', 'Екипът') }}</h2>
            <div class="section-divider"></div>
            <div class="row g-4 justify-content-center mt-2">
                @foreach($teamMembers as $member)
                    <div class="col-md-6 col-lg-4" id="person-{{ $member->id }}">
                        <article class="team-card h-100 p-4 bg-white rounded shadow-sm text-center">
                            <img src="{{ $member->image_path ? (str_starts_with($member->image_path, 'http') ? $member->image_path : asset('storage/' . $member->image_path)) : asset('css/img/default-avatar.png') }}"
                                 alt="{{ $member->name }} – {{ $member->role_bg }}" class="team-img mb-3" width="180" height="180" loading="lazy">
                            <h3 class="h5 fw-bold mb-1">{{ $member->name }}</h3>
                            <p class="role-text mb-3">{{ $member->role_bg }}</p>
                            @if($member->bio_bg)
                                <p class="small text-muted text-start">{{ strip_tags($member->bio_bg) }}</p>
                            @endif
                            @if($member->instagram_url)
                                <a href="{{ $member->instagram_url }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm rounded-0"><i class="fab fa-instagram me-1"></i> Instagram</a>
                            @endif
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <h2 class="h3 mb-3">{{ $text->get('process', 'title', 'Как работим') }}</h2>
                    <ol class="process-list">
                        <li><strong>Запитване.</strong> {{ $text->get('process', 'step1', 'Пишете или се обаждате, казвате дата, място и какво искате да запазим.') }}</li>
                        <li><strong>Оферта и договор.</strong> {{ $text->get('process', 'step2', 'Получавате конкретна цена по пакетите и екстрите. Датата се пази с договор и депозит.') }}</li>
                        <li><strong>Заснемане.</strong> {{ $text->get('process', 'step3', 'Идваме като един координиран екип: фотограф, видеооператор и при нужда дрон пилот.') }}</li>
                        <li><strong>Обработка и доставка.</strong> {{ $text->get('process', 'step4', 'Селектираме и обработваме кадрите, монтираме филма и ви даваме личен достъп до онлайн галерия в срока от договора.') }}</li>
                    </ol>
                </div>
                <div class="col-lg-6">
                    <h2 class="h3 mb-3">{{ $text->get('trust', 'title', 'Техника, договор, доставка') }}</h2>
                    <ul class="trust-list">
                        <li>{{ $text->get('trust', 'gear', 'Професионална фото и видео техника, 4K видео, дрон за въздушни кадри и мобилно осветление за зали и вечерни събития.') }}</li>
                        <li>{{ $text->get('trust', 'contract', 'Всяка резервация е с писмен договор: какво включва услугата, срокове за доставка, авторски права и условия при отмяна.') }}</li>
                        <li>{{ $text->get('trust', 'delivery', 'Готовите снимки и видео се доставят в онлайн галерия с висока резолюция, достъпна за всички участници в събитието.') }}</li>
                        <li>{{ $text->get('trust', 'area', 'Базирани сме във Варна и пътуваме до Добрич, Шумен, Балчик, Каварна, Бяла и курортите по Северното Черноморие.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light text-center">
        <div class="container">
            <h2 class="mb-3">Да поговорим за вашето събитие</h2>
            <div class="section-divider"></div>
            <a href="{{ url('/ceni') }}" class="btn-custom-full me-2">Виж цените</a>
            <a href="{{ url('/kontakti') }}" class="btn-custom">Контакти</a>
        </div>
    </section>
@endsection

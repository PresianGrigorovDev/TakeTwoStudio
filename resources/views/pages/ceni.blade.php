@extends('layouts.app')

@section('title', 'Цени за фотограф и видео във Варна 2027 | Take Two Studio')
@section('meta_description', 'Всички цени на Take Two Studio 1603 в евро: абитуриентски бал на ученик, сватбено фото и видео, кръщене, семейни фотосесии и бизнес фотография във Варна.')
@section('og_title', 'Цени за фото и видео услуги във Варна | Take Two Studio 1603')
@section('og_description', 'Прозрачни цени в евро за сватби, абитуриентски балове, кръщенета, семейни фотосесии и бизнес фотография във Варна и региона.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <h1>{{ $text->get('hero', 'title', 'Цени на фото и видео услуги във Варна (2027)') }}</h1>
            <p class="page-hero__subtitle">{{ $text->get('hero', 'subtitle', 'Всички пакети на едно място, в евро, без скрити такси') }}</p>
        </div>
    </section>
    @include('partials.breadcrumbs')

    <section class="py-5 px-1 px-1">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <p class="lead answer-capsule">{{ $text->get('intro', 'capsule', 'Тук са всички актуални цени на Take Two Studio 1603 за фото и видео услуги във Варна и региона: сватби, абитуриентски балове (цена на ученик за целия клас), кръщенета, семейни и портретни фотосесии, рекламна, автомобилна и архитектурна фотография и заснемане на събития. Цените са в евро. Точната оферта зависи от продължителността, локацията и допълнителните услуги, а калкулаторът на всяка страница дава ориентировъчна сума за минута.') }}</p>
                    @if($updatedAt)
                        <p class="text-muted small mb-4">Актуализирано на <time datetime="{{ $updatedAt->toDateString() }}">{{ $updatedAt->format('d.m.Y') }}</time></p>
                    @endif

                    <nav class="price-anchors mb-5" aria-label="Категории цени">
                        @foreach($groups as $group)
                            <a href="#{{ $group['id'] }}" class="price-anchor">{{ $group['title'] }}</a>
                        @endforeach
                    </nav>
                </div>
            </div>

            @forelse($groups as $group)
                <div class="row justify-content-center price-group" id="{{ $group['id'] }}">
                    <div class="col-lg-9">
                        <h2 class="h3 mb-2">{{ $group['title'] }}</h2>
                        @if($group['note'])
                            <p class="text-muted">{{ $group['note'] }}</p>
                        @endif
                        <div class="table-responsive">
                            <table class="table price-table align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Пакет</th>
                                        <th scope="col" class="text-end">Цена</th>
                                        <th scope="col">Какво включва</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['packages'] as $package)
                                        <tr @if($package['featured']) class="price-table__featured" @endif>
                                            <th scope="row">{{ $package['name'] }}</th>
                                            <td class="text-end text-nowrap"><strong>{{ number_format($package['price'], 0, ',', ' ') }} €</strong>@if($group['unit']) <small class="text-muted">{{ $group['unit'] }}</small>@endif</td>
                                            <td class="small">
                                                @if($package['description']){{ $package['description'] }}@endif
                                                @if($package['features'])
                                                    <ul class="mb-0 ps-3 mt-1">
                                                        @foreach($package['features'] as $feature)
                                                            <li>{{ $feature }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($group['extras']->isNotEmpty())
                            <details class="price-extras mb-3">
                                <summary>Допълнителни услуги ({{ $group['extras']->count() }})</summary>
                                <ul class="list-unstyled small mt-2 mb-0">
                                    @foreach($group['extras'] as $extra)
                                        <li class="d-flex justify-content-between border-bottom py-1"><span>{{ $extra['name'] }}@if($extra['group']) <span class="text-muted">· {{ $extra['group'] }}</span>@endif</span><span class="text-nowrap">{{ number_format($extra['price'], 0, ',', ' ') }} €</span></li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                        <p class="mb-5"><a href="{{ $group['url'] }}" class="btn-custom">Виж страницата и калкулатора</a></p>
                    </div>
                </div>
            @empty
                <div class="row justify-content-center"><div class="col-lg-9"><p class="text-muted">Цените се обновяват. Свържете се с нас за индивидуална оферта.</p></div></div>
            @endforelse

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="price-note p-4 rounded">
                        <h2 class="h5">Как се формира крайната цена</h2>
                        <p class="mb-2 small">{{ $text->get('notes', 'pricing', 'Пакетът е базата. Към него се добавят избраните екстри (дрон, втори фотограф, фотокнига, експресна обработка) и транспорт при локации извън Варна. Всяка резервация се потвърждава с договор и депозит, а остатъкът се плаща според договора.') }}</p>
                        <p class="mb-0 small">{{ $text->get('notes', 'payment', 'Издаваме фактура. За бизнес клиенти и по-големи проекти изготвяме индивидуална оферта след кратък бриф.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.faq-section', ['faqs' => $faqs, 'title' => 'Често задавани въпроси за цените'])

    <section class="py-5 px-1 bg-light text-center">
        <div class="container">
            <h2 class="mb-3">Искате точна оферта?</h2>
            <div class="section-divider"></div>
            <p class="text-muted mb-4">Опишете събитието и датата, ще отговорим с конкретна цена.</p>
            <a href="{{ url('/booking') }}" class="btn-custom-full me-2">Запази дата</a>
            <a href="{{ url('/kontakti') }}" class="btn-custom">Свържи се с нас</a>
        </div>
    </section>
@endsection

@extends('layouts.app')

@php
    $place = $gallery->place_label;
    $dateText = $gallery->event_date?->translatedFormat('d.m.Y');
    $pageTitle = \Illuminate\Support\Str::limit('Сватбата на ' . $gallery->title . ($place ? ' – ' . $place : '') . ' | Take Two Studio', 60, '');
    $pageDescription = \Illuminate\Support\Str::limit('Фото и видео от сватбата на ' . $gallery->title . ($place ? ' в ' . $place : ' във Варна') . ($dateText ? ' (' . $dateText . ')' : '') . '. Реален пример за работата на Take Two Studio 1603, сватбен фотограф и видеооператор във Варна.', 155, '');
@endphp
@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@section('og_title', 'Сватбата на ' . $gallery->title . ($place ? ' – ' . $place : ''))
@section('og_description', $pageDescription)
@section('og_image', $gallery->cover_url ?: asset('css/img/best-wedding-cover.jpg'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
@endpush

@section('content')
    <section class="story-hero" @if($gallery->cover_url) style="background-image: url('{{ $gallery->cover_url }}')" @endif>
        <div class="hero-overlay"></div>
        <div class="container position-relative">
            <p class="story-hero__kicker">Реална сватба{{ $place ? ' · ' . $place : ' · Варна' }}{{ $dateText ? ' · ' . $dateText : '' }}</p>
            <h1>Сватбата на {{ $gallery->title }}{{ $place ? ' – ' . $place : ' във Варна' }}</h1>
        </div>
    </section>
    @include('partials.breadcrumbs')

    <article class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <p class="lead answer-capsule">
                        Сватбата на {{ $gallery->title }}{{ $place ? ' в ' . $place : ' във Варна' }}{{ $dateText ? ', ' . $dateText : '' }}: снимки{{ $videoId ? ' и сватбен филм' : '' }} от Take Two Studio 1603, сватбен фотограф и видеооператор във Варна. Един екип за фото и видео, дрон кадри при подходящо време и доставка в онлайн галерия.
                    </p>
                    @if($gallery->description)
                        <div class="story-text">{!! nl2br(e($gallery->description)) !!}</div>
                    @endif
                    @if($gallery->couple_quote)
                        <blockquote class="story-quote">
                            <p class="mb-1">„{{ $gallery->couple_quote }}“</p>
                            <footer class="small text-muted">{{ $gallery->title }}</footer>
                        </blockquote>
                    @endif
                </div>
            </div>

            @if($videoId)
                <div class="row justify-content-center mt-4">
                    <div class="col-lg-9">
                        <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $videoId }}" title="Сватбен филм: {{ $gallery->title }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-3 mt-4 story-grid">
                @foreach($gallery->photos as $index => $photo)
                    @php $url = preg_match('#^https?://#i', $photo->image_path) ? $photo->image_path : asset('storage/' . $photo->image_path); @endphp
                    <div class="col-6 col-md-4">
                        <a href="{{ $url }}" class="glightbox story-photo" data-gallery="wedding-{{ $gallery->id }}" data-title="Сватбата на {{ $gallery->title }}">
                            <img src="{{ $url }}" alt="Сватбата на {{ $gallery->title }}{{ $place ? ', ' . $place : '' }} – снимка {{ $index + 1 }}, Take Two Studio 1603" loading="lazy" class="img-fluid rounded w-100 h-100 object-fit-cover">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </article>

    <section class="py-5 bg-light text-center">
        <div class="container">
            <h2 class="mb-3">Планирате сватба{{ $gallery->location ? ' в ' . $gallery->location : ' във Варна' }}?</h2>
            <div class="section-divider"></div>
            <p class="text-muted mb-4">Вижте пакетите за фото и видео или запазете дата.</p>
            <a href="{{ url('/weddings') }}" class="btn-custom-full me-2">Сватбено фото и видео</a>
            <a href="{{ url('/ceni#svatbi') }}" class="btn-custom me-2">Цени</a>
            <a href="{{ url('/booking') }}" class="btn-custom">Запази дата</a>
        </div>
    </section>

    @if($related->isNotEmpty())
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-4">Още сватби</h2>
                <div class="row g-4 justify-content-center">
                    @foreach($related as $item)
                        <div class="col-md-4">
                            <a href="{{ $item->url }}" class="text-decoration-none text-dark">
                                <div class="wedding-gallery-card rounded">
                                    <div class="gallery-cover-wrapper overflow-hidden rounded shadow-sm story-related-cover">
                                        <img src="{{ $item->cover_url }}" alt="Сватбата на {{ $item->title }}" loading="lazy" class="img-fluid w-100 h-100 object-fit-cover">
                                    </div>
                                    <div class="text-center mt-3">
                                        <h3 class="h5 fw-bold mb-1">{{ $item->title }}</h3>
                                        @if($item->place_label)<p class="small text-muted mb-0">{{ $item->place_label }}</p>@endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof GLightbox === 'function') { GLightbox({ selector: '.glightbox', touchNavigation: true, loop: true }); }
        });
    </script>
@endpush

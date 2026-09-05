@extends('layouts.app')

@section('title', 'Контакти | Take Two Studio 1603 – фотограф Варна')
@section('meta_description', 'Take Two Studio 1603, Варна: телефон, имейл, адрес ж.к. Възраждане IV 1603, работно време всеки ден 09:00–18:00. Запазете дата за фото и видео.')
@section('og_title', 'Контакти | Take Two Studio 1603')
@section('og_description', 'Телефон, имейл, адрес и работно време на фото и видео студио Take Two Studio 1603 във Варна.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <h1>{{ $text->get('hero', 'title', 'Контакти: фотограф и видеооператор във Варна') }}</h1>
            <p class="page-hero__subtitle">{{ $text->get('hero', 'subtitle', 'Отговаряме всеки ден от 09:00 до 18:00 ч.') }}</p>
        </div>
    </section>
    @include('partials.breadcrumbs')

    <section class="py-5 px-1 px-1">
        <div class="container">
            <div class="row g-4 justify-content-center text-center mb-4">
                <div class="col-md-4">
                    <i class="fas fa-phone mb-3 text-black fa-2x"></i>
                    <div class="fs-6 fw-bold"><a class="text-black text-decoration-none" href="tel:{{ \App\Support\Settings::phoneHref($phone) }}">{{ \App\Support\Settings::phoneDisplay($phone) }}</a></div>
                    @if($phoneSecondary && $phoneSecondary !== $phone)
                        <div class="small text-muted mt-1">{{ $phoneSecondaryLabel ?: 'Втори телефон' }}: <a class="text-muted" href="tel:{{ \App\Support\Settings::phoneHref($phoneSecondary) }}">{{ \App\Support\Settings::phoneDisplay($phoneSecondary) }}</a></div>
                    @endif
                </div>
                <div class="col-md-4">
                    <i class="far fa-envelope mb-3 text-black fa-2x"></i>
                    <div class="fs-6 fw-bold"><a class="text-black text-decoration-none" href="mailto:{{ $email }}">{{ $email }}</a></div>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-map-marker-alt mb-3 text-black fa-2x"></i>
                    <address class="fs-6 fw-bold mb-0">{{ $address }}</address>
                    <div class="small text-muted mt-1">Понеделник – Неделя, 09:00 – 18:00</div>
                </div>
            </div>

            @if($social)
                <p class="text-center mb-5">
                    @foreach($social as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm rounded-0 me-2 mb-2">{{ ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'google_maps' => 'Google Maps'][$network] ?? ucfirst($network) }}</a>
                    @endforeach
                </p>
            @endif

            <div class="row g-4">
                <div class="col-lg-6">
                    @if(session('success'))
                        <div class="alert alert-success mb-4 text-center">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
                    @endif
                    <form action="{{ url('/submit-contact') }}" method="post" class="h-100 p-4 border rounded shadow-sm bg-white">
                        @csrf
                        <input type="hidden" name="orderType" value="Запитване от /kontakti">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="visually-hidden" for="contact-name">Име</label><input id="contact-name" type="text" class="form-control rounded-0 p-3 bg-light border-0" placeholder="Име" name="name" value="{{ old('name') }}" required></div>
                            <div class="col-md-6"><label class="visually-hidden" for="contact-phone">Телефон</label><input id="contact-phone" type="tel" class="form-control rounded-0 p-3 bg-light border-0" placeholder="Телефон" name="phone" value="{{ old('phone') }}" required></div>
                            <div class="col-12"><label class="visually-hidden" for="contact-email">Имейл</label><input id="contact-email" type="email" class="form-control rounded-0 p-3 bg-light border-0" placeholder="Email" name="email" value="{{ old('email') }}"></div>
                            <div class="col-12"><label class="visually-hidden" for="contact-message">Съобщение</label><textarea id="contact-message" class="form-control rounded-0 p-3 bg-light border-0" rows="5" placeholder="Дата, място и какво искате да заснемем..." name="message">{{ old('message') }}</textarea></div>
                            <div class="col-12">@include('partials.gdpr-consent', ['consentId' => 'kontakti'])</div>
                            <div class="col-12"><button type="submit" class="btn-submit mt-2">Изпрати запитване</button></div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-6">
                    @include('partials.map-embed')
                    <p class="small text-muted mt-3 mb-0">{{ $text->get('info', 'note', 'Предпочитате календар? Изберете свободна дата директно от страницата за резервация.') }} <a href="{{ url('/booking') }}">Запази дата</a></p>
                </div>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('title', 'Страницата не е намерена (404) | Take Two Studio 1603')
@section('meta_description', 'Съжаляваме, но страницата, която търсите, не съществува. Върнете се към началната страница и разгледайте нашето фотографско портфолио.')
@section('meta_robots', 'noindex, follow')

@section('content')
<section class="py-5 bg-light text-center" style="min-height: 70vh; display: flex; align-items: center; justify-content: center; padding-top: 150px !important;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="display-1 fw-bold text-dark mb-3">404</h1>
                <h2 class="mb-4">Упс! Тази страница липсва от обектива.</h2>
                <div class="section-divider mx-auto mb-4"></div>
                
                <p class="lead mb-5 text-muted">
                    Изглежда, че сте попаднали на адрес, който вече не съществува или е преместен. <br>
                    Не се притеснявайте, най-добрите кадри са само на няколко клика разстояние!
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
                    <a href="{{ url('/') }}" class="btn btn-dark px-4 py-2 text-uppercase fw-bold rounded-1">Отиди в Началото</a>
                    <a href="{{ url('/#contact') }}" class="btn btn-outline-dark px-4 py-2 text-uppercase fw-bold rounded-1">Свържи се с нас</a>
                </div>

                <div class="mt-5">
                    <h5 class="text-uppercase mb-3">Или разгледай нашите услуги:</h5>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ url('weddings') }}" class="text-decoration-none border p-2 btn btn-light rounded shadow-sm">Сватби</a>
                        <a href="{{ url('proms') }}" class="text-decoration-none border p-2 btn btn-light rounded shadow-sm">Абитуриентски Балове</a>
                        <a href="{{ url('baptism') }}" class="text-decoration-none border p-2 btn btn-light rounded shadow-sm">Кръщенета</a>
                        <a href="{{ url('commercial') }}" class="text-decoration-none border p-2 btn btn-light rounded shadow-sm">Рекламна фотография</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

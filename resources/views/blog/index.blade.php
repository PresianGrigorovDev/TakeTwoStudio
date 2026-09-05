@extends('layouts.app')

@section('title', 'Блог | Take Two Studio 1603 - Съвети, истории и вдъхновение')
@section('meta_title', 'Блог | Take Two Studio 1603')
@section('meta_description', 'Статии, съвети и истории от света на фотографията и видеозаснемането. Идеи за сватби, кръщенета, балове и корпоративни събития във Варна и цяла България.')
@section('meta_keywords', 'блог фотография, съвети сватбен фотограф, идеи фотосесия, Take Two Studio блог')
@section('og_title', 'Блог | Take Two Studio 1603')
@section('og_description', 'Съвети, истории и вдъхновение от Take Two Studio - вашият фотограф във Варна.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('content')
    <section class="blog-hero">
        <div class="container">
            <h1 class="blog-hero__title" data-aos="fade-up">Блог на Take Two Studio</h1>
            <p class="blog-hero__subtitle" data-aos="fade-up" data-aos-delay="100">
                Съвети, истории зад кадъра и вдъхновение за вашите специални моменти
            </p>
        </div>
    </section>

    <section class="blog-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    @if($posts->isEmpty())
                        <div class="blog-empty">
                            <i class="fas fa-newspaper"></i>
                            <h3>Скоро тук ще има публикации</h3>
                            <p>Работим върху нови статии. Върнете се скоро!</p>
                        </div>
                    @else
                        <div class="blog-grid">
                            @foreach($posts as $post)
                                @include('blog.partials.card', ['post' => $post])
                            @endforeach
                        </div>

                        <div class="blog-pagination">
                            {{ $posts->links() }}
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    @include('blog.partials.sidebar')
                </div>
            </div>
        </div>
    </section>
@endsection
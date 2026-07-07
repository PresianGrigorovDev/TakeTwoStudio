@extends('layouts.app')

@section('title', $category->name . ' | Блог | Take Two Studio 1603')
@section('meta_title', $category->name . ' — публикации от блога на Take Two Studio')
@section('meta_description', $category->description ?: 'Публикации в категория ' . $category->name . ' от блога на Take Two Studio 1603.')
@section('og_title', $category->name . ' | Блог | Take Two Studio')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('content')
    <section class="blog-hero">
        <div class="container">
            <nav class="blog-breadcrumb">
                <a href="{{ route('blog.index') }}">Блог</a>
                <span>/</span>
                <span>{{ $category->name }}</span>
            </nav>
            <h1 class="blog-hero__title" data-aos="fade-up">{{ $category->name }}</h1>
            @if($category->description)
                <p class="blog-hero__subtitle" data-aos="fade-up" data-aos-delay="100">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    <section class="blog-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    @if($posts->isEmpty())
                        <div class="blog-empty">
                            <i class="fas fa-newspaper"></i>
                            <h3>Няма публикации в тази категория все още</h3>
                            <p><a href="{{ route('blog.index') }}">Вижте всички публикации</a></p>
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
                    @include('blog.partials.sidebar', ['activeCategorySlug' => $category->slug])
                </div>
            </div>
        </div>
    </section>
@endsection

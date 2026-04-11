@extends('layouts.app')

@section('title', ($post->meta_title ?: $post->title) . ' | Блог Take Two Studio 1603')
@section('meta_title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 160))
@section('meta_keywords', $post->meta_keywords ?: '')
@section('og_title', $post->meta_title ?: $post->title)
@section('og_description', $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 160))
@section('og_image', $post->og_image_url ?: asset('css/img/social-share-cover.jpg'))

@push('schema')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": {!! json_encode($post->title) !!},
        "image": {!! json_encode($post->cover_image_url) !!},
        "datePublished": "{{ $post->published_at?->toIso8601String() }}",
        "dateModified": "{{ $post->updated_at?->toIso8601String() }}",
        "author": {
            "@type": "Organization",
            "name": "Take Two Studio 1603",
            "url": "{{ url('/') }}"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Take Two Studio 1603",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ asset('css/img/logo-tts-white.webp') }}"
            }
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "{{ url()->current() }}"
        },
        "description": {!! json_encode($post->meta_description ?: strip_tags($post->excerpt)) !!}
    }
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('content')
    <article class="blog-post">
        <header class="blog-post__hero" @if($post->cover_image) style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('{{ $post->cover_image_url }}');" @endif>
            <div class="container">
                <nav class="blog-breadcrumb blog-breadcrumb--light">
                    <a href="{{ route('blog.index') }}">Блог</a>
                    @if($post->category)
                        <span>/</span>
                        <a href="{{ route('blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>
                    @endif
                </nav>

                @if($post->category)
                    <span class="blog-post__category"
                          @if($post->category->color) style="background-color: {{ $post->category->color }};" @endif>
                        {{ $post->category->name }}
                    </span>
                @endif

                <h1 class="blog-post__title" data-aos="fade-up">{{ $post->title }}</h1>

                <div class="blog-post__meta">
                    <time datetime="{{ $post->published_at?->toIso8601String() }}">
                        <i class="far fa-calendar"></i>
                        {{ $post->published_at?->translatedFormat('d F Y') }}
                    </time>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="blog-post__excerpt" data-aos="fade-up">
                        {{ $post->excerpt }}
                    </div>

                    <div class="blog-post__body" data-aos="fade-up">
                        {!! $post->body !!}
                    </div>

                    <div class="blog-post__cta" data-aos="fade-up">
                        <div class="blog-post__cta-content">
                            <h3>Хареса ви статията?</h3>
                            <p>Ако планирате специален момент, ние сме тук да го заснемем. Свържете се с нас за запитване или резервация.</p>
                            <div class="blog-post__cta-buttons">
                                <a href="{{ url('/booking') }}" class="btn btn-primary">Запази сесия</a>
                                <a href="{{ url('/#contact') }}" class="btn btn-outline">Запитване</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($relatedPosts->isNotEmpty())
                <section class="blog-related" data-aos="fade-up">
                    <h2 class="blog-related__title">Свързани публикации</h2>
                    <div class="blog-related__grid">
                        @foreach($relatedPosts as $related)
                            @include('blog.partials.card', ['post' => $related])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
@endsection

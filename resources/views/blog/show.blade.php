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

                    @php
                        $categoryCtaMap = [
                            'wedding-tips' => [
                                'url' => url('/weddings'),
                                'heading' => 'Планирате сватба?',
                                'text' => 'Разгледайте нашите сватбени пакети и вижте как можем да заснемем вашия ден.',
                                'label' => 'Към сватбена фотография',
                            ],
                            'baptism-tips' => [
                                'url' => url('/baptism'),
                                'heading' => 'Предстои ви кръщене?',
                                'text' => 'Вижте как заснемаме кръщенета и какво включват нашите пакети.',
                                'label' => 'Към фотография на кръщенета',
                            ],
                            'prom-tips' => [
                                'url' => url('/proms'),
                                'heading' => 'Абитуриент сте?',
                                'text' => 'Разгледайте нашите предложения за бал и пред-балните фотосесии.',
                                'label' => 'Към балните фотосесии',
                            ],
                            'photoshoot-style' => [
                                'url' => url('/portrait'),
                                'heading' => 'Искате своя фотосесия?',
                                'text' => 'Запазете индивидуална портретна сесия и създайте своите кадри с нас.',
                                'label' => 'Към портретни фотосесии',
                            ],
                            'event-tips' => [
                                'url' => url('/events'),
                                'heading' => 'Организирате събитие?',
                                'text' => 'Разгледайте нашите услуги за фото и видеозаснемане на фирмени и лични събития.',
                                'label' => 'Към фото и видео за събития',
                            ],
                        ];

                        // Some posts fit a more specific landing page than their category's default CTA.
                        $postSlugCtaOverrides = [
                            'uchilishtno-izprashtane-zasnemane-varna' => [
                                'url' => url('/graduation'),
                                'heading' => 'Предстои изпращане на випуска?',
                                'text' => 'Вижте нашите пред-бални фотосесии и пакети за изпращане на абитуриенти.',
                                'label' => 'Към пред-балните фотосесии',
                            ],
                        ];

                        $categoryCta = $postSlugCtaOverrides[$post->slug]
                            ?? ($post->category ? ($categoryCtaMap[$post->category->slug] ?? null) : null);
                    @endphp

                    <div class="blog-post__cta" data-aos="fade-up">
                        <div class="blog-post__cta-content">
                            @if($categoryCta)
                                <h3>{{ $categoryCta['heading'] }}</h3>
                                <p>{{ $categoryCta['text'] }}</p>
                                <div class="blog-post__cta-buttons">
                                    <a href="{{ $categoryCta['url'] }}" class="btn btn-primary">{{ $categoryCta['label'] }}</a>
                                </div>
                            @else
                                <h3>Хареса ви статията?</h3>
                                <p>Ако планирате специален момент, ние сме тук да го заснемем. Разгледайте нашите услуги и резервирайте своята сесия.</p>
                                <div class="blog-post__cta-buttons">
                                    <a href="{{ url('/booking') }}" class="btn btn-primary">Запази сесия</a>
                                </div>
                            @endif
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

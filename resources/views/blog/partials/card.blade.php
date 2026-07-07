@php /** @var \App\Models\BlogPost $post */ @endphp
<article class="blog-card" data-aos="fade-up">
    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__image-link">
        @if($post->cover_image)
            <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" loading="lazy" class="blog-card__image">
        @endif
        @if($post->category)
            <span class="blog-card__category-badge"
                  @if($post->category->color) style="background-color: {{ $post->category->color }};" @endif>
                {{ $post->category->name }}
            </span>
        @endif
    </a>
    <div class="blog-card__body">
        <time class="blog-card__date" datetime="{{ $post->published_at?->toIso8601String() }}">
            {{ $post->published_at?->translatedFormat('d F Y') }}
        </time>
        <h3 class="blog-card__title">
            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
        </h3>
        <p class="blog-card__excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 140) }}</p>
        <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__more">
            Прочети още <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</article>

@php
    /** @var \App\Models\BlogPost $post */
    // aboveFold: the first cards of a listing (first screen on desktop) get no AOS - it would hide them until AOS.init and then fade them in.
    // lcp: the first card image is the mobile LCP candidate - eager + high priority instead of lazy.
    $aboveFold ??= false;
    $lcp ??= false;
    // h2 in the blog listings (right under the page h1), h3 under "Свързани публикации" on a post.
    $titleTag = ($titleTag ?? null) === 'h2' ? 'h2' : 'h3';
@endphp
<article class="blog-card"@unless($aboveFold) data-aos="fade-up"@endunless>
    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__image-link">
        @if($post->cover_image)
            <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" @if($lcp) fetchpriority="high"@else loading="lazy"@endif class="blog-card__image">
        @endif
        @if($post->category)
            <span class="blog-card__category-badge"
                  @if($post->category->color) style="background-color: {{ $post->category->color }}; color: @include('blog.partials.badge-ink', ['hex' => $post->category->color]);" @endif>
                {{ $post->category->name }}
            </span>
        @endif
    </a>
    <div class="blog-card__body">
        <time class="blog-card__date" datetime="{{ $post->published_at?->toIso8601String() }}">
            {{ $post->published_at?->translatedFormat('d F Y') }}
        </time>
        <{{ $titleTag }} class="blog-card__title">
            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
        </{{ $titleTag }}>
        <p class="blog-card__excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 140) }}</p>
        <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__more">
            Прочети още <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</article>

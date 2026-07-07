@php
    /** @var \Illuminate\Support\Collection $categories */
    /** @var \Illuminate\Support\Collection $recentPosts */
    $activeCategorySlug = $activeCategorySlug ?? null;
@endphp
<aside class="blog-sidebar">
    @if($categories->isNotEmpty())
        <div class="blog-sidebar__block">
            <h4 class="blog-sidebar__title">Категории</h4>
            <ul class="blog-sidebar__list">
                <li @class(['is-active' => is_null($activeCategorySlug)])>
                    <a href="{{ route('blog.index') }}">Всички публикации</a>
                </li>
                @foreach($categories as $category)
                    <li @class(['is-active' => $activeCategorySlug === $category->slug])>
                        <a href="{{ route('blog.category', $category->slug) }}">
                            {{ $category->name }}
                            <span class="count">{{ $category->published_posts_count ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($recentPosts->isNotEmpty())
        <div class="blog-sidebar__block">
            <h4 class="blog-sidebar__title">Последни публикации</h4>
            <ul class="blog-sidebar__recent">
                @foreach($recentPosts as $recent)
                    <li>
                        <a href="{{ route('blog.show', $recent->slug) }}" class="blog-sidebar__recent-link">
                            @if($recent->cover_image)
                                <img src="{{ $recent->cover_image_url }}" alt="{{ $recent->title }}" loading="lazy">
                            @endif
                            <div>
                                <span class="blog-sidebar__recent-title">{{ \Illuminate\Support\Str::limit($recent->title, 55) }}</span>
                                <time>{{ $recent->published_at?->translatedFormat('d M Y') }}</time>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="blog-sidebar__block blog-sidebar__cta">
        <h4 class="blog-sidebar__title">Нужен ви е фотограф?</h4>
        <p>Свържете се с нас за запитване или резервация на дата.</p>
        <a href="{{ url('/booking') }}" class="blog-sidebar__cta-btn">Запази сесия</a>
    </div>
</aside>

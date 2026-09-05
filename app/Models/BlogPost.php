<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

use App\Traits\LogsActivity;
use App\Support\ImageOptimizer;

class BlogPost extends Model
{
    use LogsActivity;
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'category_id',
        'published_at',
        'is_published',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'views_count',
        'author_team_member_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'views_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (BlogPost $post) {
            if ($post->is_published && $post->published_at && $post->published_at->isPast()) {
                \App\Support\Seo\IndexNow::submitLater([route('blog.show', $post->slug), route('blog.index')]);
            }
        });

        static::saving(function (BlogPost $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            if (empty($post->meta_keywords)) {
                $post->meta_keywords = $post->generateMetaKeywords();
            }
        });

        static::saved(function (BlogPost $post) {
            if ($post->wasChanged('cover_image') && ! empty($post->cover_image)) {
                ImageOptimizer::optimize('public', $post->cover_image);
            }
        });
    }

    /**
     * Bulgarian stopwords to filter out when extracting keywords.
     */
    private const STOPWORDS = [
        'на', 'в', 'във', 'с', 'със', 'за', 'и', 'или', 'но', 'ако', 'от', 'до', 'по',
        'при', 'че', 'то', 'той', 'тя', 'те', 'аз', 'ти', 'ние', 'вие', 'се', 'си',
        'са', 'е', 'бил', 'била', 'било', 'били', 'съм', 'сме', 'сте', 'а', 'да', 'не',
        'като', 'така', 'също', 'още', 'ще', 'би', 'бих', 'бяхме', 'бяхте', 'бяха',
        'който', 'която', 'което', 'които', 'един', 'една', 'едно', 'едни', 'този',
        'тази', 'това', 'тези', 'такъв', 'такава', 'такова', 'такива', 'мой', 'твой',
        'наш', 'ваш', 'техен', 'към', 'без', 'върху', 'под', 'над', 'пред', 'зад',
        'около', 'след', 'преди', 'между', 'през', 'освен', 'много', 'малко', 'всеки',
        'всяка', 'всяко', 'всички', 'само', 'вече', 'тук', 'там', 'къде', 'кога',
        'какво', 'защо', 'как', 'няма', 'има', 'бъде', 'беше',
    ];

    /**
     * Brand keywords injected into every post for SEO consistency.
     */
    private const BRAND_KEYWORDS = [
        'Take Two Studio',
        'сватбен фотограф Варна',
        'видеозаснемане',
        'професионален фотограф',
        'фотограф Варна',
    ];

    public function generateMetaKeywords(): string
    {
        $collected = [];

        foreach (self::BRAND_KEYWORDS as $brand) {
            $collected[] = mb_strtolower($brand);
        }

        if ($this->category?->name) {
            $collected[] = mb_strtolower($this->category->name);
        }

        $sources = [
            (string) $this->title,
            (string) $this->excerpt,
        ];

        foreach ($sources as $text) {
            foreach ($this->extractKeywords($text) as $kw) {
                $collected[] = $kw;
            }
        }

        $unique = [];
        foreach ($collected as $kw) {
            $kw = trim($kw);
            if ($kw === '' || isset($unique[$kw])) {
                continue;
            }
            $unique[$kw] = true;
            if (\count($unique) >= 15) {
                break;
            }
        }

        return implode(', ', array_keys($unique));
    }

    private function extractKeywords(string $text): array
    {
        $clean = strip_tags($text);
        $clean = mb_strtolower($clean);
        // Split on anything that isn't a Cyrillic/Latin letter or digit
        $words = preg_split('/[^\p{L}\p{N}]+/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $keywords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) < 4) {
                continue;
            }
            if (\in_array($word, self::STOPWORDS, true)) {
                continue;
            }
            $keywords[] = $word;
        }

        return $keywords;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'author_team_member_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '>', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_published', false)
                ->orWhereNull('published_at');
        });
    }

    public function getUrlAttribute(): string
    {
        return route('blog.show', $this->slug);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->cover_image);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->og_image ?: $this->cover_image);
    }

    /** Shown instead of a broken image when a cover file has gone missing on disk. */
    public const FALLBACK_COVER = 'css/img/default-placeholder.jpg';

    private function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // Static public assets (seeded placeholders)
        if (str_starts_with($path, 'css/') || str_starts_with($path, 'img/')) {
            return is_file(public_path($path)) ? asset($path) : asset(self::FALLBACK_COVER);
        }

        // Filament uploads land in storage/app/public/
        return Storage::disk('public')->exists($path) ? asset('storage/' . $path) : asset(self::FALLBACK_COVER);
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_published) {
            return 'draft';
        }
        if ($this->published_at && $this->published_at->isFuture()) {
            return 'scheduled';
        }
        return 'published';
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}

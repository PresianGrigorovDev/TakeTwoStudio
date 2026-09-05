<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\Seo\ServiceCatalog;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * /sitemap.xml is a sitemap index pointing at pages, blog and images sitemaps.
 * lastmod is derived from the data that actually renders on each URL
 * (service, page content, FAQs, packages, galleries, posts) - never "today".
 * changefreq/priority are omitted: Google ignores them.
 */
class SitemapController extends Controller
{
    private const TTL = 3600;

    /** slug => [package tables, gallery/photo tables] that change what the page shows */
    private const SERVICE_SOURCES = [
        'weddings' => [['service_packages', 'service_extras'], ['wedding_galleries', 'wedding_gallery_photos']],
        'proms' => [['prom_packages'], ['prom_portfolio_photos']],
        'baptism' => [['service_packages', 'service_extras'], ['baptism_galleries', 'baptism_gallery_photos']],
        'commercial' => [['service_packages', 'service_extras', 'commercial_packages'], ['commercial_portfolio_photos']],
        'family' => [['family_packages'], ['family_galleries', 'family_gallery_photos']],
        'portrait' => [['portrait_packages'], ['portrait_galleries', 'portrait_gallery_photos', 'portrait_portfolio_photos']],
        'automotive' => [['automotive_packages'], ['automotive_galleries', 'automotive_gallery_photos']],
        'architectural' => [['architectural_packages'], ['architectural_galleries', 'architectural_gallery_photos']],
        'events' => [['event_packages'], ['event_galleries', 'event_gallery_photos', 'event_portfolio_photos']],
    ];

    /** slug => photo tables whose image_path feeds the image sitemap */
    private const IMAGE_SOURCES = [
        'weddings' => ['wedding_gallery_photos'],
        'proms' => ['prom_portfolio_photos'],
        'baptism' => ['baptism_gallery_photos'],
        'commercial' => ['commercial_portfolio_photos'],
        'family' => ['family_gallery_photos'],
        'portrait' => ['portrait_gallery_photos', 'portrait_portfolio_photos'],
        'automotive' => ['automotive_gallery_photos'],
        'architectural' => ['architectural_gallery_photos'],
        'events' => ['event_gallery_photos', 'event_portfolio_photos'],
    ];

    public function index(): Response
    {
        return $this->xml('sitemap.index', fn () => [
            'sitemaps' => [
                ['loc' => url('/sitemap-pages.xml'), 'lastmod' => $this->latest($this->pageEntries())],
                ['loc' => url('/sitemap-blog.xml'), 'lastmod' => $this->latest($this->blogEntries())],
                ['loc' => url('/sitemap-images.xml'), 'lastmod' => $this->latest($this->pageEntries())],
            ],
        ], 'index');
    }

    public function pages(): Response
    {
        return $this->xml('sitemap.urlset', fn () => ['urls' => $this->pageEntries()], 'pages');
    }

    public function blog(): Response
    {
        return $this->xml('sitemap.urlset', fn () => ['urls' => $this->blogEntries()], 'blog');
    }

    public function images(): Response
    {
        return $this->xml('sitemap.images', fn () => ['urls' => $this->imageEntries()], 'images');
    }

    /** @return array<int,array{loc:string,lastmod:?string}> */
    private function pageEntries(): array
    {
        $services = array_keys(self::SERVICE_SOURCES);
        $serviceDates = collect($services)->mapWithKeys(fn ($slug) => [$slug => $this->serviceLastmod($slug)]);

        $homeDate = $this->max([
            ...$serviceDates->values()->all(),
            $this->tableMax('team_members'),
            $this->tableMax('testimonials'),
            $this->tableMax('partners'),
            $this->tableMax('portfolio_categories'),
        ]);

        $entries = [['loc' => rtrim(url('/'), '/').'/', 'lastmod' => $this->fmt($homeDate)]];

        foreach ($services as $slug) {
            $entries[] = ['loc' => url($slug), 'lastmod' => $this->fmt($serviceDates[$slug])];
        }

        $entries[] = ['loc' => url('/ceni'), 'lastmod' => $this->fmt($this->max(array_merge(
            $serviceDates->values()->all(),
            [$this->tableMax('faqs', ['page_slug' => 'ceni']), $this->tableMax('page_contents', ['page_slug' => 'ceni'])]
        )))];
        $entries[] = ['loc' => url('/abiturientski-bal-varna'), 'lastmod' => $this->fmt($this->max([$serviceDates['proms'], $this->tableMax('faqs', ['page_slug' => 'abiturientski-bal-varna']), $this->tableMax('page_contents', ['page_slug' => 'abiturientski-bal-varna'])]))];
        $entries[] = ['loc' => url('/za-nas'), 'lastmod' => $this->fmt($this->max([$this->tableMax('team_members'), $this->tableMax('page_contents', ['page_slug' => 'za-nas']), $this->tableMax('testimonials')]))];
        $entries[] = ['loc' => url('/kontakti'), 'lastmod' => $this->fmt($this->max([$this->tableMax('site_settings'), $this->tableMax('page_contents', ['page_slug' => 'kontakti'])]))];
        $entries[] = ['loc' => url('/booking'), 'lastmod' => $this->fmt($this->max([$this->tableMax('blocked_dates'), $this->tableMax('services')]))];
        $entries[] = ['loc' => url('/blog'), 'lastmod' => $this->fmt($this->max([BlogPost::published()->max('updated_at'), BlogPost::published()->max('published_at')]))];

        foreach (\App\Models\WeddingGallery::where('is_active', true)->whereNotNull('slug')->orderByDesc('event_date')->get() as $gallery) {
            $entries[] = ['loc' => route('weddings.story', $gallery->slug), 'lastmod' => $this->fmt($gallery->updated_at)];
        }

        foreach (['privacy', 'terms', 'cookies'] as $legal) {
            $entries[] = ['loc' => url($legal), 'lastmod' => $this->fmt($this->tableMax('legal_pages', ['slug' => $legal]))];
        }

        return $entries;
    }

    /** @return array<int,array{loc:string,lastmod:?string}> */
    private function blogEntries(): array
    {
        $entries = [];

        foreach (BlogCategory::where('is_visible', true)->get() as $category) {
            $latestPost = BlogPost::published()->where('category_id', $category->id)->max('updated_at');
            $entries[] = ['loc' => route('blog.category', $category->slug), 'lastmod' => $this->fmt($this->max([$category->updated_at, $latestPost]))];
        }

        foreach (BlogPost::published()->orderByDesc('published_at')->get() as $post) {
            $entries[] = ['loc' => route('blog.show', $post->slug), 'lastmod' => $this->fmt($this->max([$post->updated_at, $post->published_at]))];
        }

        return $entries;
    }

    /** @return array<int,array{loc:string,lastmod:?string,images:array<int,string>}> */
    private function imageEntries(): array
    {
        $entries = [];

        foreach (self::IMAGE_SOURCES as $slug => $tables) {
            $images = [];

            if ($hero = DB::table('services')->where('slug', $slug)->value('hero_image')) {
                $images[] = $this->imageUrl($hero);
            }

            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $query = DB::table($table)->whereNotNull('image_path')->orderBy('id');

                if (Schema::hasColumn($table, 'is_visible')) {
                    $query->where('is_visible', true);
                }

                foreach ($query->limit(1000)->pluck('image_path') as $path) {
                    $images[] = $this->imageUrl($path);
                }
            }

            $images = array_values(array_unique(array_filter($images)));

            if ($images) {
                $entries[] = ['loc' => url($slug), 'lastmod' => $this->fmt($this->serviceLastmod($slug)), 'images' => array_slice($images, 0, 1000)];
            }
        }

        foreach (\App\Models\WeddingGallery::with('photos')->where('is_active', true)->whereNotNull('slug')->get() as $gallery) {
            $images = array_values(array_filter(array_map(fn ($p) => $this->imageUrl($p), array_merge([$gallery->cover_image], $gallery->photos->pluck('image_path')->all()))));
            if ($images) {
                $entries[] = ['loc' => route('weddings.story', $gallery->slug), 'lastmod' => $this->fmt($gallery->updated_at), 'images' => $images];
            }
        }

        return $entries;
    }

    private function serviceLastmod(string $slug): ?Carbon
    {
        [$packageTables, $mediaTables] = self::SERVICE_SOURCES[$slug];

        $dates = [
            $this->tableMax('services', ['slug' => $slug]),
            $this->tableMax('page_contents', ['page_slug' => $slug]),
            $this->tableMax('faqs', ['page_slug' => $slug]),
        ];

        $serviceId = DB::table('services')->where('slug', $slug)->value('id');

        foreach ($packageTables as $table) {
            $dates[] = in_array($table, ['service_packages', 'service_extras'], true)
                ? ($serviceId ? $this->tableMax($table, ['service_id' => $serviceId]) : null)
                : $this->tableMax($table);
        }

        foreach ($mediaTables as $table) {
            $dates[] = $this->tableMax($table);
        }

        return $this->max($dates);
    }

    private function tableMax(string $table, array $where = []): ?Carbon
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $value = DB::table($table)->where($where)->max('updated_at');

        return $value ? Carbon::parse($value) : null;
    }

    /** @param array<int,Carbon|string|null> $dates */
    private function max(array $dates): ?Carbon
    {
        return collect($dates)
            ->filter()
            ->map(fn ($d) => $d instanceof Carbon ? $d : Carbon::parse($d))
            ->max();
    }

    private function latest(array $entries): ?string
    {
        return collect($entries)->pluck('lastmod')->filter()->max();
    }

    private function fmt(?Carbon $date): ?string
    {
        return $date?->toDateString();
    }

    private function imageUrl(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        return preg_match('#^https?://#i', $path) ? $path : asset('storage/'.ltrim($path, '/'));
    }

    private function xml(string $view, callable $data, string $cacheKey): Response
    {
        $body = Cache::remember("sitemap.{$cacheKey}.v2", self::TTL, fn () => view($view, $data())->render());

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.self::TTL,
        ]);
    }
}

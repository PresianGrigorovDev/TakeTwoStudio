<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\Seo\Seo;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::published()
            ->with('category')
            ->latest('published_at')
            ->paginate(9);

        $categories = BlogCategory::where('is_visible', true)
            ->orderBy('display_order')
            ->withCount(['publishedPosts'])
            ->get();

        $recentPosts = BlogPost::published()
            ->latest('published_at')
            ->take(5)
            ->get();

        app(Seo::class)->setPageType('CollectionPage');

        return view('blog.index', compact('posts', 'categories', 'recentPosts'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $post->incrementViews();

        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        $this->registerPostSeo($post);

        return view('blog.show', compact('post', 'relatedPosts'));
    }

    public function category(string $slug)
    {
        $category = BlogCategory::where('slug', $slug)
            ->where('is_visible', true)
            ->firstOrFail();

        $posts = BlogPost::published()
            ->with('category')
            ->where('category_id', $category->id)
            ->latest('published_at')
            ->paginate(9);

        $categories = BlogCategory::where('is_visible', true)
            ->orderBy('display_order')
            ->withCount(['publishedPosts'])
            ->get();

        $recentPosts = BlogPost::published()
            ->latest('published_at')
            ->take(5)
            ->get();

        app(Seo::class)->setPageType('CollectionPage');

        return view('blog.category', compact('category', 'posts', 'categories', 'recentPosts'));
    }

    /** schema.org BlogPosting with a Person author (team member) when one is assigned. */
    private function registerPostSeo(BlogPost $post): void
    {
        $seo = app(Seo::class)->setDates($post->published_at, $post->updated_at);
        $current = url()->current();

        $author = $post->author;
        if ($author) {
            $seo->addNode(PageController::personNode($author));
        }

        $body = strip_tags((string) $post->body);
        preg_match_all('/\pL+/u', $body, $words);

        $seo->addNode(array_filter([
            '@type' => 'BlogPosting',
            '@id' => $current.'#article',
            'headline' => $post->title,
            'description' => $post->meta_description ?: Str::limit(strip_tags((string) $post->excerpt), 160),
            'image' => $post->cover_image_url ? [$post->cover_image_url] : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => ['@id' => $author ? Seo::rootId('person-'.$author->id) : Seo::rootId('organization')],
            'publisher' => ['@id' => Seo::rootId('organization')],
            'mainEntityOfPage' => ['@id' => $current.'#webpage'],
            'articleSection' => $post->category?->name,
            'inLanguage' => 'bg',
            'wordCount' => count($words[0]) ?: null,
            'url' => $current,
        ]));
    }
}

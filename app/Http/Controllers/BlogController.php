<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;

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

        return view('blog.category', compact('category', 'posts', 'categories', 'recentPosts'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;

class SitemapController extends Controller
{
    public function index()
    {
        $pages = [
            ['loc' => url('/'),           'priority' => '1.00', 'changefreq' => 'weekly',  'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/weddings'),   'priority' => '0.90', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/proms'),      'priority' => '0.90', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/baptism'),    'priority' => '0.90', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/commercial'),  'priority' => '0.90', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/graduation'), 'priority' => '0.90', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/family'),        'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/portrait'),      'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/automotive'),    'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/architectural'), 'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/events'),        'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/booking'),       'priority' => '0.70', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => url('/blog'),          'priority' => '0.85', 'changefreq' => 'weekly',  'lastmod' => now()->format('Y-m-d')],
        ];

        foreach (BlogCategory::where('is_visible', true)->get() as $category) {
            $pages[] = [
                'loc' => route('blog.category', $category->slug),
                'priority' => '0.70',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at->format('Y-m-d'),
            ];
        }

        foreach (BlogPost::published()->get() as $post) {
            $pages[] = [
                'loc' => route('blog.show', $post->slug),
                'priority' => '0.75',
                'changefreq' => 'monthly',
                'lastmod' => $post->updated_at->format('Y-m-d'),
            ];
        }

        return response()
            ->view('sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml');
    }
}

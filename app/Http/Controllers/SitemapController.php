<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $pages = [
            ['loc' => $baseUrl . '/',           'priority' => '1.00', 'changefreq' => 'weekly',  'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/proms',      'priority' => '0.95', 'changefreq' => 'weekly',  'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/weddings',   'priority' => '0.95', 'changefreq' => 'weekly',  'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/family',     'priority' => '0.85', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/portrait',   'priority' => '0.85', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/baptism',    'priority' => '0.85', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/commercial', 'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/automotive', 'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/architectural', 'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/events',        'priority' => '0.80', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/booking',       'priority' => '0.70', 'changefreq' => 'monthly', 'lastmod' => now()->format('Y-m-d')],
            ['loc' => $baseUrl . '/blog',          'priority' => '0.85', 'changefreq' => 'weekly',  'lastmod' => now()->format('Y-m-d')],
        ];

        foreach (BlogCategory::where('is_visible', true)->get() as $category) {
            $pages[] = [
                'loc' => $baseUrl . '/blog/category/' . $category->slug,
                'priority' => '0.70',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at ? $category->updated_at->format('Y-m-d') : now()->format('Y-m-d'),
            ];
        }

        foreach (BlogPost::published()->get() as $post) {
            $pages[] = [
                'loc' => $baseUrl . '/blog/' . $post->slug,
                'priority' => '0.75',
                'changefreq' => 'monthly',
                'lastmod' => $post->updated_at ? $post->updated_at->format('Y-m-d') : now()->format('Y-m-d'),
            ];
        }

        return response()
            ->view('sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml');
    }
}

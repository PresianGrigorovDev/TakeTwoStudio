<?php

namespace App\Support\Seo;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

/**
 * Derives the breadcrumb trail (Начало › ... › current page) from the current
 * route. Controllers can override via Seo::setBreadcrumbs().
 */
final class Breadcrumbs
{
    public const HOME_LABEL = 'Начало';

    public const BLOG_LABEL = 'Блог';

    /** @return array<int,array{name:string,url:?string}> */
    public static function guess(): array
    {
        $request = request();
        $route = $request->route();
        $routeName = $route?->getName();
        $path = trim($request->getPathInfo(), '/');

        $home = ['name' => self::HOME_LABEL, 'url' => url('/')];

        if ($path === '') {
            return [$home];
        }

        if ($routeName === 'blog.index') {
            return [$home, ['name' => self::BLOG_LABEL, 'url' => null]];
        }

        if ($routeName === 'blog.category') {
            $name = BlogCategory::where('slug', $route->parameter('slug'))->value('name') ?? self::BLOG_LABEL;

            return [$home, ['name' => self::BLOG_LABEL, 'url' => route('blog.index')], ['name' => $name, 'url' => null]];
        }

        if ($routeName === 'blog.show') {
            $post = BlogPost::with('category')->where('slug', $route->parameter('slug'))->first();
            $trail = [$home, ['name' => self::BLOG_LABEL, 'url' => route('blog.index')]];

            if ($post?->category) {
                $trail[] = ['name' => $post->category->name, 'url' => route('blog.category', $post->category->slug)];
            }

            $trail[] = ['name' => $post?->title ?? ucfirst((string) $route->parameter('slug')), 'url' => null];

            return $trail;
        }

        if (ServiceCatalog::has($path)) {
            return [$home, ['name' => self::serviceLabel($path), 'url' => null]];
        }

        return [$home, ['name' => self::genericLabel($path, $routeName), 'url' => null]];
    }

    public static function serviceLabel(string $slug): string
    {
        $dbName = Cache::remember("seo.service_name.{$slug}", 3600, fn () => Service::where('slug', $slug)->value('name_bg'));

        return $dbName ?: (ServiceCatalog::get($slug, 'breadcrumb') ?? ucfirst($slug));
    }

    private static function genericLabel(string $path, ?string $routeName): string
    {
        return match (true) {
            $path === 'booking' => 'Резервация',
            $path === 'ceni' => 'Цени',
            $path === 'za-nas' => 'За нас',
            $path === 'kontakti' => 'Контакти',
            $path === 'abiturientski-bal-varna' => 'Абитуриентски бал Варна 2027',
            $routeName === 'legal.privacy' => 'Политика за поверителност',
            $routeName === 'legal.terms' => 'Общи условия',
            $routeName === 'legal.cookies' => 'Политика за бисквитки',
            default => ucfirst(str_replace(['-', '_', '/'], ' ', $path)),
        };
    }
}

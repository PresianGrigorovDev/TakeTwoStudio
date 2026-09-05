<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application-level fallback for the canonical-URL rules in the project-root
 * .htaccess. Collapses every duplicate URL variant (http://, www., a /public
 * or /index.php base path, trailing slash) onto the single canonical
 * https://<APP_URL host>/<path> with one 301, for GET/HEAD requests only.
 *
 * Detection uses Request::getBaseUrl(), which is exactly the part Symfony
 * strips before routing (e.g. "/public" when the front controller was reached
 * as /public/index.php) - $request->path() never contains it.
 */
class NormalizeCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.force_canonical') || app()->environment('local')) {
            return $next($request);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $root = rtrim((string) config('app.url'), '/');

        if (! str_starts_with($root, 'https://')) {
            return $next($request);
        }

        $canonicalHost = (string) parse_url($root, PHP_URL_HOST);
        $pathInfo = $request->getPathInfo();

        $wrongHost = strcasecmp($request->getHost(), $canonicalHost) !== 0;
        $wrongScheme = ! $request->isSecure();
        $hasBasePath = $request->getBaseUrl() !== '';
        $trailingSlash = $pathInfo !== '/' && str_ends_with($pathInfo, '/');

        if (! ($wrongHost || $wrongScheme || $hasBasePath || $trailingSlash)) {
            return $next($request);
        }

        $path = '/'.trim($pathInfo, '/');
        $query = $request->server->get('QUERY_STRING') ?: $request->getQueryString();

        $url = $root.$path.($query !== null && $query !== '' ? '?'.$query : '');

        return redirect()->to($url, 301);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collapses the duplicate URL variants Google has indexed
 * (www, http, and the "/public" prefixed paths) onto the one
 * canonical https://non-www URL, independent of any server-level
 * .htaccess/vhost misconfiguration.
 */
class NormalizeCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('get') && ! $request->isMethod('head')) {
            return $next($request);
        }

        $host = $request->getHost();
        $path = $request->path();

        $newHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        $newPath = $path;
        if ($path === 'public') {
            $newPath = '/';
        } elseif (str_starts_with($path, 'public/')) {
            $newPath = substr($path, strlen('public/'));
        }

        if ($newHost !== $host || $newPath !== $path || ! $request->secure()) {
            $query = $request->getQueryString();
            $url = 'https://'.$newHost.'/'.ltrim($newPath, '/').($query ? '?'.$query : '');

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}

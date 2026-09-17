<?php

use App\Http\Middleware\NormalizeCanonicalUrl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(NormalizeCanonicalUrl::class);

        // QR game events are anonymous, cookie-free and sent via fetch/sendBeacon (no headers possible): no CSRF token to verify.
        $middleware->validateCsrfTokens(except: ['api/igra/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

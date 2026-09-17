<!DOCTYPE html>
<html lang="bg">
<head>
    {{--
        Bare layout for the QR sticker game (/igra). Deliberately shares nothing with layouts/app.blade.php:
        no navbar/footer, no cookie banner or analytics (the page sets no cookies and stores no personal data),
        no Bootstrap/FontAwesome/AOS. All styling lives in public/css/igra.css (PagesRenderTest forbids inline <style>).
        Never create a public/igra/ directory: the root .htaccess returns 403 for URLs that resolve to a real directory.
    --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="@yield('theme_color', '#050807')">
    <meta name="robots" content="noindex, nofollow">
    <meta name="format-detection" content="telephone=no">

    <title>@yield('title', 'Игра | Take Two Studio 1603')</title>
    <meta name="description" content="@yield('meta_description')">

    <meta property="og:type" content="website">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:title" content="@yield('og_title')">
    <meta property="og:description" content="@yield('meta_description')">
    <meta property="og:image" content="{{ asset('css/img/social-share-cover.jpg') }}">
    <meta property="og:locale" content="bg_BG">
    <meta property="og:site_name" content="Take Two Studio 1603">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title')">
    <meta name="twitter:description" content="@yield('meta_description')">
    <meta name="twitter:image" content="{{ asset('css/img/social-share-cover.jpg') }}">

    <link rel="preload" href="{{ asset('fonts/montserrat/montserrat-400-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/montserrat/montserrat-700-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('fonts/montserrat/montserrat.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Assets::versioned('css/igra.css') }}">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('css/img/favicon_io/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('css/img/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    @stack('head')
</head>
<body class="igra" data-target="{{ $target }}" data-screen="intro">
    @yield('content')

    <script type="application/json" id="igra-config">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
    <script src="{{ \App\Support\Assets::versioned('js/igra.js') }}" defer></script>
    @stack('scripts')
</body>
</html>

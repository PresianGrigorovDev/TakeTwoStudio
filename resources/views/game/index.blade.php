@extends('layouts.game')

@section('title', $page['title'].' | '.$config['studioName'])
@section('meta_description', $page['description'])
@section('theme_color', $page['theme'])
@section('og_title', $page['title'])
@section('og_url', $shareUrl)

@section('content')
<noscript>
    <p class="igra-noscript">Играта изисква включен JavaScript. Пиши ни в Instagram: {{ '@'.$config['instagramHandle'] }}</p>
</noscript>

<main class="igra-app" id="igra-app">
    <header class="igra-top">
        <span class="igra-tag mono">// {{ $config['studioName'] }}</span>
        <button type="button" class="igra-icon-btn" id="btn-mute" aria-pressed="false" aria-label="Звук">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="24" height="24">
                <path class="ico-speaker" d="M4 9v6h3.5l4.5 4V5L7.5 9H4z"/>
                <path class="ico-waves" d="M15.5 8.5a5 5 0 0 1 0 7M18 6a8.5 8.5 0 0 1 0 12"/>
                <path class="ico-x" d="M15.5 9.5l5 5m0-5l-5 5"/>
            </svg>
        </button>
    </header>

    <p class="igra-unsupported" id="igra-unsupported" hidden>Браузърът ти е твърде стар за тази игра. Отвори линка в Safari или Chrome.</p>

    {{-- INTRO --}}
    <section class="igra-screen igra-intro" data-screen="intro" id="screen-intro">
        @if($target === 'wedding')
            <h1 class="igra-title igra-title--terminal" id="igra-h1" tabindex="-1"><span class="type" data-text="ЛОГИЧЕСКИ ПЪЗЕЛ: СВАТБЕНАТА МАСА">ЛОГИЧЕСКИ ПЪЗЕЛ: СВАТБЕНАТА МАСА</span><span class="cursor" aria-hidden="true"></span></h1>
            <p class="igra-lead mono">&gt; 6 гости. 6 места. 4 правила. Един верен план.</p>
            <button type="button" class="igra-btn igra-btn--primary" id="btn-start">Започни</button>
        @else
            <h1 class="igra-title glitch" id="igra-h1" tabindex="-1" data-text="ПРОТОКОЛ: ИЗЛИЗАНЕ ОТ МАТРИЦАТА [ВАРНА]">ПРОТОКОЛ: ИЗЛИЗАНЕ ОТ МАТРИЦАТА [ВАРНА]</h1>
            <p class="igra-lead mono">&gt; 16 плочки. 4 скрити групи по 4. 4 опита.</p>
            <button type="button" class="igra-btn igra-btn--primary" id="btn-start">Влез в системата</button>
        @endif
    </section>

    {{-- GAME --}}
    <section class="igra-screen" data-screen="game" id="screen-game" hidden>
        @include($target === 'wedding' ? 'game.partials.seating' : 'game.partials.connections')
    </section>

    {{-- REVEAL --}}
    <section class="igra-screen rv" data-screen="reveal" id="screen-reveal" hidden>
        <p class="rv-boot mono" id="rv-boot" aria-hidden="true"></p>
        <h2 class="rv-title" id="rv-title" tabindex="-1">Загадката е разгадана.</h2>
        <p class="rv-text">Ти виждаш детайлите, които останалите пропускат. Точно така улавяме и твоите моменти.</p>
        <div class="rv-brand">
            <img src="{{ $config['logoUrl'] }}" alt="{{ $config['studioName'] }}" class="rv-logo" decoding="async">
            <span class="rv-rule" aria-hidden="true"></span>
        </div>
        <div class="rv-card" id="rv-card">
            <span class="rv-badge">{{ $config['discountPercent'] }}% OFF VOUCHER</span>
            <p class="rv-card__sub">{{ $config['discountPercent'] }}% отстъпка за пълно фото и видео заснемане на {{ $target === 'wedding' ? 'сватбата ти' : 'бала ти – Бал '.$config['seasonYear'] }}.</p>
            <div class="rv-code-row">
                <code class="rv-code mono" id="rv-code" aria-label="Твоят код">VN-····-····</code>
                <button type="button" class="igra-btn igra-btn--ghost igra-btn--sm" id="rv-copy">Копирай</button>
            </div>
            <p class="rv-timer mono" id="rv-timer" role="timer" aria-live="off">Валиден {{ $config['validityHours'] }} часа</p>
        </div>
        <div class="rv-story">
            <button type="button" class="igra-btn igra-btn--primary" id="rv-generate">Генерирай Story Артефакт</button>
            <p class="igra-msg mono" id="rv-story-msg" role="status"></p>
            <figure class="rv-preview" id="rv-preview" hidden>
                <img alt="Story артефакт с кода за отстъпка" id="rv-preview-img">
                <figcaption class="mono" id="rv-preview-hint"></figcaption>
            </figure>
            <div class="igra-actions igra-actions--inline" id="rv-story-actions" hidden>
                <button type="button" class="igra-btn igra-btn--primary" id="rv-share" hidden>Сподели</button>
                <button type="button" class="igra-btn igra-btn--primary" id="rv-download" hidden>Свали артефакта</button>
            </div>
        </div>
        <ol class="rv-steps">
            <li>Свали артефакта.</li>
            <li>Качи го на Instagram Story с таг <a href="{{ $config['instagramUrl'] }}" target="_blank" rel="noopener">{{ '@'.$config['instagramHandle'] }}</a>.</li>
            <li>Изпрати ни кода на лично съобщение в рамките на {{ $config['validityHours'] }} часа, за да запазим датата ти.</li>
        </ol>
        <a class="rv-ig igra-btn igra-btn--ghost" href="{{ $config['instagramUrl'] }}" target="_blank" rel="noopener">Отвори {{ '@'.$config['instagramHandle'] }}</a>
        @if(!empty($config['legalUrl']))
            <footer class="rv-foot mono"><a href="{{ $config['legalUrl'] }}">Условия на играта и ваучера</a></footer>
        @endif
    </section>

    <p class="visually-hidden" id="igra-live" aria-live="polite" aria-atomic="true"></p>
</main>
@endsection

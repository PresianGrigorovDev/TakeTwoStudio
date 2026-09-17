{{-- Modal content of the "QR код" table action on Маркетинг → QR стикери. --}}
<div class="space-y-4">
    @include('filament.partials.game-qr-panel', [
        'url' => $sticker->url,
        'name' => $sticker->target.'-'.$sticker->loc,
        'logos' => [
            'dark' => asset('css/img/logo-tts-white.webp'),
            'light' => asset('css/img/logo-tts-black.png'),
        ],
    ])

    <p class="text-xs text-gray-400">
        Стикер „{{ $sticker->name }}“ · {{ \App\Filament\Resources\GameEventResource::TARGET_LABELS[$sticker->target] ?? $sticker->target }} · loc=<span class="font-mono">{{ $sticker->loc }}</span>.
        Сканиранията на този QR код се броят към стикера в статистиката.
    </p>
</div>

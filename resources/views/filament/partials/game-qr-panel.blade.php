{{--
    QR generator panel (Alpine). Expects: $url (string), $name (file-name friendly), $logos (['dark' => url, 'light' => url]).
    Depends on the panel-registered assets vendor/qrcode/qrcode.min.js and js/admin/game-qr.js (see AdminPanelProvider).
--}}
<div
    class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_260px] gap-5"
    x-data="GameQr.panel({ url: @js($url), name: @js($name), logos: @js($logos) })"
>
    <div class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label class="block">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Стил</span>
                <select x-model="style" x-on:change="render()" class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <option value="dark">Черен фон, бели модули</option>
                    <option value="light">Бял фон, черни модули (класически)</option>
                    <option value="gold">Черен фон, златни модули</option>
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Център</span>
                <select x-model="center" x-on:change="render()" class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <option value="question">Въпросителна „?“</option>
                    <option value="logo">Лого на студиото</option>
                    <option value="none">Без</option>
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Модули</span>
                <select x-model="dots" x-on:change="render()" class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <option value="square">Квадратни</option>
                    <option value="round">Кръгли</option>
                </select>
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select x-model="size" class="fi-select-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                <option value="1024">PNG 1024 px</option>
                <option value="2048">PNG 2048 px</option>
                <option value="4096">PNG 4096 px</option>
            </select>
            <x-filament::button color="primary" size="sm" icon="heroicon-m-arrow-down-tray" x-on:click="downloadPng()" x-bind:disabled="busy || !url">
                Свали PNG
            </x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-m-arrow-down-tray" x-on:click="downloadSvg()" x-bind:disabled="!url">
                Свали SVG (за печат)
            </x-filament::button>
            <x-filament::button
                color="gray"
                size="sm"
                icon="heroicon-m-clipboard-document"
                x-on:click="window.navigator.clipboard.writeText(url); if (window.FilamentNotification) { new FilamentNotification().title('Линкът е копиран').success().send() }"
            >
                Копирай линка
            </x-filament::button>
        </div>

        <p class="text-xs text-gray-400">
            Корекция на грешки „H“ (30%), затова центърът може да бъде покрит. Версия на кода: <span x-text="version"></span>.
            <span x-show="isInverted()" x-cloak>Обърнатите кодове (светли модули на тъмен фон) се четат от камерите на iPhone и Android, но не от всички стари скенери – тествай с 2–3 телефона преди печат; класическият стил е най-сигурен.</span>
        </p>
    </div>

    <div class="flex items-start justify-center">
        <div class="w-[260px] max-w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 [&_svg]:w-full [&_svg]:h-auto [&_svg]:block" x-html="svgMarkup"></div>
    </div>
</div>

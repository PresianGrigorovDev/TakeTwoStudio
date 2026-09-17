<x-filament-panels::page>
    @assets
        <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>
        <script src="{{ \App\Support\Assets::versioned('js/admin/game-qr.js') }}"></script>
    @endassets

    @php($generated = $this->getGeneratedUrl())
    @php($known = $this->getKnownLocations())

    {{-- Game links + QR generator --}}
    <x-filament::section>
        <x-slot name="heading">Линкове и QR кодове към игрите</x-slot>
        <x-slot name="description">Всеки стикер получава своя локация (loc), за да се вижда в статистиката кой стикер работи. QR кодът се генерира тук от линка и се сваля готов за печат.</x-slot>

        <div class="space-y-6">
            {{-- Base links --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($this->getBaseLinks() as $label => $url)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">{{ $label }} – без локация</div>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 min-w-0 truncate text-sm font-mono text-primary-600 dark:text-primary-400" title="{{ $url }}">{{ $url }}</code>
                            <x-filament::icon-button
                                icon="heroicon-m-clipboard-document"
                                color="gray"
                                label="Копирай линка"
                                x-data="{}"
                                x-on:click="window.navigator.clipboard.writeText(@js($url)); if (window.FilamentNotification) { new FilamentNotification().title('Линкът е копиран').success().send() }"
                            />
                            <x-filament::icon-button
                                icon="heroicon-m-arrow-top-right-on-square"
                                color="gray"
                                label="Отвори играта"
                                tag="a"
                                :href="$url"
                                target="_blank"
                                rel="noopener"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Generator: link + QR --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Линк и QR код за стикер</div>
                {{ $this->form }}

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <code class="flex-1 min-w-0 truncate text-sm font-mono font-semibold text-primary-600 dark:text-primary-400" id="game-generated-url" title="{{ $generated }}">{{ $generated }}</code>
                    <x-filament::button
                        icon="heroicon-m-clipboard-document"
                        color="primary"
                        size="sm"
                        x-data="{}"
                        x-on:click="window.navigator.clipboard.writeText(@js($generated)); if (window.FilamentNotification) { new FilamentNotification().title('Линкът е копиран').success().send() }"
                    >
                        Копирай линка
                    </x-filament::button>
                    <x-filament::button
                        icon="heroicon-m-arrow-top-right-on-square"
                        color="gray"
                        size="sm"
                        tag="a"
                        :href="$generated"
                        target="_blank"
                        rel="noopener"
                    >
                        Отвори
                    </x-filament::button>
                </div>

                {{-- QR panel (Alpine; re-renders whenever the Livewire link changes) --}}
                <div
                    class="mt-5 grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_260px] gap-5"
                    id="game-qr-panel"
                    wire:ignore
                    x-data="GameQr.panel({ url: @js($generated), name: @js($this->stickerName), logos: @js($this->getLogoUrls()) })"
                    x-effect="setUrl($wire.generatedUrl, $wire.stickerName)"
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
                        </div>

                        <p class="text-xs text-gray-400">
                            Корекция на грешки „H“ (30%), затова центърът може да бъде покрит. Версия: <span x-text="version"></span>.
                            <span x-show="isInverted()" x-cloak>Обърнатите кодове (светли модули на тъмен фон) се четат от камерите на iPhone и Android, но не от всички стари скенери – винаги тествай с 2–3 телефона преди печат; класическият стил е най-сигурен.</span>
                        </p>
                    </div>

                    <div class="flex items-start justify-center">
                        <div class="w-[260px] max-w-full rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 [&_svg]:w-full [&_svg]:h-auto [&_svg]:block" x-html="svgMarkup"></div>
                    </div>
                </div>
            </div>

            {{-- Known locations --}}
            <div>
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Стикери, които вече са сканирани</div>
                @if(empty($known))
                    <p class="text-sm text-gray-400">Все още няма сканирани стикери с локация.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-1.5 pr-3 font-medium">Игра</th>
                                    <th class="text-left py-1.5 px-2 font-medium">Локация</th>
                                    <th class="text-right py-1.5 px-2 font-medium">Сканирания</th>
                                    <th class="text-left py-1.5 px-2 font-medium">Линк</th>
                                    <th class="py-1.5 pl-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($known as $row)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="py-1.5 pr-3">{{ $row['label'] }}</td>
                                        <td class="py-1.5 px-2 font-mono font-semibold text-primary-600">{{ $row['loc'] }}</td>
                                        <td class="py-1.5 px-2 text-right tabular-nums">{{ $row['scans'] }}</td>
                                        <td class="py-1.5 px-2 font-mono text-xs text-gray-600 dark:text-gray-300 truncate max-w-xs" title="{{ $row['url'] }}">{{ $row['url'] }}</td>
                                        <td class="py-1.5 pl-2 whitespace-nowrap">
                                            <x-filament::icon-button
                                                icon="heroicon-m-qr-code"
                                                color="primary"
                                                label="Зареди в генератора за QR"
                                                size="sm"
                                                wire:click="setSticker(@js($row['target']), @js($row['loc']))"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-m-clipboard-document"
                                                color="gray"
                                                label="Копирай линка"
                                                size="sm"
                                                x-data="{}"
                                                x-on:click="window.navigator.clipboard.writeText(@js($row['url'])); if (window.FilamentNotification) { new FilamentNotification().title('Линкът е копиран').success().send() }"
                                            />
                                            <x-filament::icon-button
                                                icon="heroicon-m-arrow-top-right-on-square"
                                                color="gray"
                                                label="Отвори играта"
                                                size="sm"
                                                tag="a"
                                                :href="$row['url']"
                                                target="_blank"
                                                rel="noopener"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Как да валидираш код от Instagram DM</x-slot>

        <ol class="list-decimal pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-300">
            <li>Отвори „Всички събития и кодове“ и потърси кода (напр. <span class="font-mono font-semibold">VN-PROM-K7M3</span>).</li>
            <li>Трябва да има ред „Ваучер“ с този код от последните 72 часа и да не е отбелязан като използван.</li>
            <li>Провери, че клиентът е качил Story артефакта с таг на студиото.</li>
            <li>След като приложиш отстъпката, натисни „Маркирай като използван“ на реда – така кодът не може да се ползва втори път.</li>
        </ol>

        <p class="mt-3 text-xs text-gray-400">
            Кодовете от играта се генерират в браузъра на клиента и НЕ работят в калкулаторите на сайта – отстъпката се прилага ръчно при офертата.
            Играта не събира лични данни: записват се само игра, локация на стикера (<span class="font-mono">loc</span> от QR адреса), събитие, код и час.
        </p>
    </x-filament::section>
</x-filament-panels::page>

<x-filament-panels::page>
    {{-- Game links: base links, per-sticker generator, and locations already seen. --}}
    <x-filament::section>
        <x-slot name="heading">Линкове към игрите</x-slot>
        <x-slot name="description">Това са адресите, които се кодират в QR стикерите. Всеки стикер получава своя локация (loc), за да се вижда в статистиката кой стикер работи.</x-slot>

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

            {{-- Generator --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Линк за нов стикер</div>
                {{ $this->form }}

                @php($generated = $this->getGeneratedUrl())
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <code class="flex-1 min-w-0 truncate text-sm font-mono font-semibold text-primary-600 dark:text-primary-400" id="game-generated-url" title="{{ $generated }}">{{ $generated }}</code>
                    <x-filament::button
                        icon="heroicon-m-clipboard-document"
                        color="primary"
                        size="sm"
                        x-data="{}"
                        x-on:click="window.navigator.clipboard.writeText(@js($generated)); if (window.FilamentNotification) { new FilamentNotification().title('Линкът е копиран').success().send() }"
                    >
                        Копирай
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
                <p class="mt-2 text-xs text-gray-400">QR кодът за стикера се генерира от този адрес (напр. с генератора на печатницата). Препоръка: корекция на грешки „H“, за да се чете и надраскан.</p>
            </div>

            {{-- Known locations --}}
            @php($known = $this->getKnownLocations())
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

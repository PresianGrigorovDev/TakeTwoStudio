<x-filament-panels::page>
    @php($known = $this->getKnownLocations())

    <x-filament::section>
        <x-slot name="heading">Настройки на играта</x-slot>
        <x-slot name="description">Каква отстъпка печели играчът при победа и колко става, ако сподели Story артефакта с таг на студиото. Промяната важи за новите ваучери; вече издадените пазят процента, който играчът е видял.</x-slot>

        <form wire:submit="saveSettings" class="space-y-4">
            {{ $this->form }}
            <div class="flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-m-check">Запази настройките</x-filament::button>
                <span class="text-xs text-gray-400">Валидност на ваучера: {{ \App\Support\GameVoucher::VALIDITY_HOURS }} часа. Кодът работи в калкулаторите на сайта и се отбелязва като използван при поръчка.</span>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Сканирани локации</x-slot>
        <x-slot name="description">Всяка локация (loc от QR адреса), от която има поне едно сканиране. Линковете и QR кодовете се създават и свалят от „QR стикери“.</x-slot>

        @if(empty($known))
            <p class="text-sm text-gray-400">Все още няма сканирани стикери с локация. Създай стикер в „QR стикери“, свали QR кода и го сканирай, за да тестваш.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-1.5 pr-3 font-medium">Игра</th>
                            <th class="text-left py-1.5 px-2 font-medium">Локация</th>
                            <th class="text-right py-1.5 px-2 font-medium">Сканирания</th>
                            <th class="text-left py-1.5 px-2 font-medium">Стикер</th>
                            <th class="py-1.5 pl-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($known as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-1.5 pr-3">{{ $row['label'] }}</td>
                                <td class="py-1.5 px-2 font-mono font-semibold text-primary-600">{{ $row['loc'] }}</td>
                                <td class="py-1.5 px-2 text-right tabular-nums">{{ $row['scans'] }}</td>
                                <td class="py-1.5 px-2">
                                    @if($row['sticker'])
                                        <a href="{{ \App\Filament\Resources\GameStickerResource::getUrl('edit', ['record' => $row['sticker']]) }}" class="text-primary-600 hover:underline">{{ $row['sticker']->name }}</a>
                                    @else
                                        <span class="text-gray-400">не е регистриран</span>
                                    @endif
                                </td>
                                <td class="py-1.5 pl-2 whitespace-nowrap text-right">
                                    @if(! $row['sticker'])
                                        <x-filament::link :href="$row['createUrl']" size="sm" icon="heroicon-m-plus">Създай стикер</x-filament::link>
                                    @endif
                                    <x-filament::icon-button
                                        icon="heroicon-m-clipboard-document"
                                        color="gray"
                                        label="Копирай линка"
                                        size="sm"
                                        x-data="{}"
                                        x-on:click="window.navigator.clipboard.writeText(@js($row['url'])); if (window.FilamentNotification) { new FilamentNotification().title('Линкът е копиран').success().send() }"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Как да валидираш код от Instagram DM</x-slot>

        <ol class="list-decimal pl-5 space-y-1 text-sm text-gray-600 dark:text-gray-300">
            <li>Отвори „Всички събития и кодове“ и потърси кода (напр. <span class="font-mono font-semibold">VN-PROM-K7M3</span>).</li>
            <li>Трябва да има ред „Ваучер“ с този код от последните 72 часа и да не е отбелязан като използван. Колоната „Отстъпка“ показва процента и дали Story-то е споделено.</li>
            <li>Ако клиентът иска увеличената отстъпка, провери, че е качил Story артефакта с таг на студиото.</li>
            <li>След като приложиш отстъпката, натисни „Маркирай като използван“ на реда – така кодът не може да се ползва втори път.</li>
        </ol>

        <p class="mt-3 text-xs text-gray-400">
            Кодовете от играта работят и директно в калкулаторите на сайта (бутон „Използвай кода в сайта“ на победния екран): при поръчка се прилага текущият процент на ваучера и кодът се отбелязва като използван автоматично.
            Играта не събира лични данни: записват се само игра, локация на стикера, събитие, код и час.
        </p>
    </x-filament::section>
</x-filament-panels::page>

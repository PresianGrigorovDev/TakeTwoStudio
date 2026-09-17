<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            QR Игра – статистика по локация
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::link :href="$resourceUrl" size="sm" icon="heroicon-m-magnifying-glass">
                Провери код от DM
            </x-filament::link>
        </x-slot>

        @if(empty($stats))
            <p class="text-gray-500 text-sm">Все още няма събития от играта.</p>
        @else
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                @foreach($stats as $target => $group)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3 uppercase tracking-wide">
                            {{ $group['label'] }}
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-3 font-medium">Локация</th>
                                        <th class="text-right py-1.5 px-2 font-medium">Сканирания</th>
                                        <th class="text-right py-1.5 px-2 font-medium">Победи</th>
                                        <th class="text-right py-1.5 px-2 font-medium">Загуби</th>
                                        <th class="text-right py-1.5 px-2 font-medium">Ваучери</th>
                                        <th class="text-right py-1.5 px-2 font-medium">Споделяния</th>
                                        <th class="text-right py-1.5 pl-2 font-medium" title="Победи / сканирания · ваучери / сканирания">Конверсия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['rows'] as $row)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="py-1.5 pr-3 font-mono font-semibold text-primary-600">{{ $row['loc'] }}</td>
                                            <td class="py-1.5 px-2 text-right tabular-nums">{{ $row['scans'] }}</td>
                                            <td class="py-1.5 px-2 text-right tabular-nums text-green-600">{{ $row['wins'] }}</td>
                                            <td class="py-1.5 px-2 text-right tabular-nums text-gray-500">{{ $row['losses'] }}</td>
                                            <td class="py-1.5 px-2 text-right tabular-nums font-medium">{{ $row['vouchers'] }}</td>
                                            <td class="py-1.5 px-2 text-right tabular-nums">{{ $row['shares'] }}</td>
                                            <td class="py-1.5 pl-2 text-right tabular-nums text-xs text-gray-500 dark:text-gray-400">
                                                @if($row['win_rate'] === null)
                                                    –
                                                @else
                                                    {{ $row['win_rate'] }}% · {{ $row['voucher_rate'] }}%
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-semibold text-gray-700 dark:text-gray-200">
                                        <td class="py-1.5 pr-3">Общо</td>
                                        <td class="py-1.5 px-2 text-right tabular-nums">{{ $group['totals']['scans'] }}</td>
                                        <td class="py-1.5 px-2 text-right tabular-nums text-green-600">{{ $group['totals']['wins'] }}</td>
                                        <td class="py-1.5 px-2 text-right tabular-nums text-gray-500">{{ $group['totals']['losses'] }}</td>
                                        <td class="py-1.5 px-2 text-right tabular-nums">{{ $group['totals']['vouchers'] }}</td>
                                        <td class="py-1.5 px-2 text-right tabular-nums">{{ $group['totals']['shares'] }}</td>
                                        <td class="py-1.5 pl-2 text-right tabular-nums text-xs">
                                            @if($group['totals']['win_rate'] === null)
                                                –
                                            @else
                                                {{ $group['totals']['win_rate'] }}% · {{ $group['totals']['voucher_rate'] }}%
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 text-xs text-gray-400">
                Конверсия = победи / сканирания · ваучери / сканирания. Кодовете от играта НЕ работят в калкулаторите –
                валидират се ръчно по код + дата (72 ч) + Instagram Story с таг.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

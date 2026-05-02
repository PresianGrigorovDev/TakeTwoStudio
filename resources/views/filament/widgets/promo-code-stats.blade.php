<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Статистики – Промо Кодове
        </x-slot>

        @if(collect($stats['revenue_by_code'])->isEmpty() && collect($stats['by_source'])->isEmpty())
            <p class="text-gray-500 text-sm">Все още няма поръчки с промо кодове.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Top codes by usage --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3 uppercase tracking-wide">
                        Топ кодове по ползвания
                    </h3>
                    @forelse($stats['top_codes'] as $code)
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                            <span class="font-mono text-sm font-semibold text-primary-600">{{ $code->code }}</span>
                            <span class="text-sm text-gray-500">{{ $code->orders_count }} поръчки</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Няма данни.</p>
                    @endforelse
                </div>

                {{-- Revenue per code --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3 uppercase tracking-wide">
                        Приходи по промо код
                    </h3>
                    @forelse($stats['revenue_by_code'] as $row)
                        <div class="flex flex-col py-1.5 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex justify-between">
                                <span class="font-mono text-sm font-semibold text-primary-600">{{ $row->promo_code }}</span>
                                <span class="text-sm font-medium text-green-600">€{{ number_format($row->total_revenue, 0) }}</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-400 mt-0.5">
                                <span>{{ $row->orders_count }} поръчки</span>
                                @if($row->total_discounted)
                                    <span>Спестено: €{{ number_format($row->total_discounted, 0) }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Няма данни.</p>
                    @endforelse
                </div>

                {{-- By source / channel --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3 uppercase tracking-wide">
                        Ползвания по канал
                    </h3>
                    @forelse($stats['by_source'] as $row)
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $row->source }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                {{ $row->total_uses }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Няма данни.</p>
                    @endforelse
                </div>

            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

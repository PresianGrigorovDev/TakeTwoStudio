<x-filament-panels::page>
    <x-filament::tabs label="Секции на услугата">
        @foreach ($this->getTabs() as $key => $item)
            <x-filament::tabs.item
                :active="$tab === $key"
                :icon="$item['icon']"
                :badge="$item['badge'] ?: null"
                wire:click="$set('tab', '{{ $key }}')"
            >
                {{ $item['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    @if ($tab === 'settings')
        @livewire(\App\Livewire\ServiceHubSettings::class, ['service' => $record], key('settings-' . $record->id))
    @else
        @livewire(\App\Livewire\ServiceHubTable::class, ['serviceId' => $record->id, 'section' => $tab], key($record->id . '-' . $tab))
    @endif
</x-filament-panels::page>

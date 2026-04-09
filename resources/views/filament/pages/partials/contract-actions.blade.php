<div class="flex gap-3 justify-end">
    <x-filament::button
        color="gray"
        icon="heroicon-o-eye"
        wire:click="mountAction('preview')"
    >
        Преглед (PDF)
    </x-filament::button>

    <x-filament::button
        icon="heroicon-o-arrow-down-tray"
        wire:click="mountAction('download')"
    >
        Изтегли PDF
    </x-filament::button>
</div>

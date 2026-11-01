<div>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">Запази</x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>

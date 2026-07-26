<x-layouts::app :title="__('Character')">
    <livewire:campaign-selector-banner />
    <div class="mt-2 mx-auto flex w-full max-w-2xl flex-col gap-2">
        <flux:heading size="xl">Character</flux:heading>

        <livewire:player-character-page />
    </div>
</x-layouts::app>

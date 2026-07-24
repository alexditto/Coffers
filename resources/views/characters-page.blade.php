<x-layouts::app :title="__('Characters')">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-2">
        <flux:heading size="xl">Characters</flux:heading>
        <livewire:list-characters-page />
    </div>
</x-layouts::app>

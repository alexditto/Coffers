<x-layouts::app :title="__('Journal')">
    <livewire:campaign-selector-banner />
    <div class="mt-2 mx-auto flex w-full max-w-2xl flex-col gap-2">
        <livewire:journal-page />
    </div>
</x-layouts::app>

<x-layouts::app :title="__('Journal')">
    <div class="hidden lg:block">
        <livewire:campaign-selector-banner/>
    </div>
    <div class="mt-2 mx-auto flex w-full max-w-2xl flex-col gap-2">
        <livewire:journal-page />
    </div>
</x-layouts::app>

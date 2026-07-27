<x-layouts::app :title="__('Shops')">
    <div class="hidden lg:block">
        <livewire:campaign-selector-banner/>
    </div>
    <div class="mt-2 mx-auto flex w-full max-w-2xl flex-col gap-2">
        <flux:heading size="xl">Shops</flux:heading>
        <livewire:shop-page />
    </div>
</x-layouts::app>

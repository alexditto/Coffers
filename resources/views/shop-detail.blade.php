<x-layouts::app :title="__('Shop')">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-2">
        <livewire:shop-detail-page :shop-id="(int) $shop" />
    </div>
</x-layouts::app>

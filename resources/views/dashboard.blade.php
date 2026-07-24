<x-layouts::app :title="__('Dashboard')">
    <div class="mb-4">
        <livewire:campaign-selector-banner/>
    </div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <livewire:dashboard-upcoming-campaign/>
            <livewire:dashboard-friends/>
        </div>
        <div
            class="relative h-1/3 flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20"/>
        </div>
    </div>
</x-layouts::app>

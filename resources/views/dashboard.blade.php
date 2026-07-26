<x-layouts::app :title="__('Dashboard')">
    <div class="mb-4 hidden lg:block">
        <livewire:campaign-selector-banner/>
    </div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <livewire:dashboard-friends/>
            <livewire:dashboard-upcoming-campaign/>
        </div>
        <x-dashboard-info />
    </div>
</x-layouts::app>

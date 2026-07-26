<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex-1 h-full overflow-y-auto bg-gray-100 dark:bg-gray-900">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

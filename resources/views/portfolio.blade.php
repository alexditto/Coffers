<x-layouts::auth.simple>
    <div class="flex flex-col items-center gap-4 text-center">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-800 text-brand-300">
            <flux:icon.code-bracket class="size-5" />
        </span>

        <div>
            <flux:heading size="lg">Portfolio coming soon</flux:heading>
            <flux:text class="mt-2">
                This page is still being built. Check back soon to see what else I've been working on.
            </flux:text>
        </div>

        <flux:button :href="route('home')" wire:navigate variant="ghost" icon="arrow-left">
            Back to home
        </flux:button>
    </div>
</x-layouts::auth.simple>

<x-layouts::auth.landingCard>
    <flux:main class="flex w-full flex-col gap-8">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium mt-20" wire:navigate>
                    <span class="flex h-24 w-24 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white"/>
                    </span>
            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
        </a>
        <div class="text-center text-4xl font-semibold tracking-tight text-neutral-950 dark:text-white">
            Coffer
        </div>
        <div class="text-center text-lg text-neutral-500 dark:text-neutral-400">Your character, the shops, the loot and
            the lore — in your pocket, at the table.
        </div>
        <div class="flex flex-col gap-3">
            <flux:button :href="route('register')" variant="primary" class="w-full bg-brand-primary hover:bg-brand-secondary hover:border-b-brand-primary" data-test="register-button">
                {{ __('Create an Account') }}
            </flux:button>

            <flux:button :href="route('login')" variant="primary" class="w-full bg-white text-brand-secondary hover:bg-brand-secondary hover:text-white" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </div>
    </flux:main>
</x-layouts::auth.landingCard>

<x-layouts::auth.landingCard >
    <flux:main>
        <div class="flex items-center justify-end mb-6">
            <flux:button :href="route('register')" variant="primary" type="submit" class="w-full bg-brand-primary hover:bg-brand-secondary hover:border-b-brand-primary" data-test="login-button">
                {{ __('Create an Account') }}
            </flux:button>
        </div>
        <div class="flex items-center justify-end">
            <flux:button :href="route('login')" variant="primary" type="submit" class="w-full bg-white text-brand-secondary hover:bg-brand-secondary hover:text-white" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </div>
    </flux:main>
</x-layouts::auth.landingCard>

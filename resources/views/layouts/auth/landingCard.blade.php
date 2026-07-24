<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
<div class="bg-background flex flex-col gap-6 p-6 md:p-10 min-h-screen lg:flex-row lg:justify-center lg:items-center">
    <div class="flex w-full h-full max-w-sm flex-col gap-2">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium mt-20" wire:navigate>
                    <span class="flex h-24 w-24 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white"/>
                    </span>
            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
        </a>
        <div class="text-center text-4xl font-semibold tracking-tight text-neutral-950 dark:text-white">
            Coffer
        </div>
        <div class="text-center text-lg text-neutral-500 dark:text-neutral-400">Your character, the shops, the loot and the lore — in your pocket, at the table.</div>
    </div>
    <div class="flex flex-col grow items-end w-full md:w-3/4 max-w-lg bg-white dark:bg-neutral-950">
        {{ $slot }}
    </div>
</div>

@persist('toast')
<flux:toast.group>
    <flux:toast/>
</flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>

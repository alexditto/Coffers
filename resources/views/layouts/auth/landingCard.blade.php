<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
<div class="bg-background flex flex-col-reverse gap-6 p-6 md:p-10 min-h-screen lg:flex-row lg:justify-center lg:items-center">
    <div class="flex w-full h-full max-w-sm flex-col gap-2 items-center justify-center">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <a
                href="{{ route('portfolio') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-2xl border border-dashed border-neutral-700 p-4 text-sm text-neutral-400 transition hover:border-brand-400 hover:text-neutral-200"
            >
                <flux:icon.code-bracket class="size-5 shrink-0 text-brand-400"/>
                <span>Hey, did you want to hire a nerdy software developer who builds things like this?</span>
            </a>
        </div>
        <div class="max-w-2xl mx-auto my-4 rounded-2xl border border-neutral-800 bg-neutral-900/60 p-6">
            <span class="text-[10px] font-bold tracking-widest text-brand-300 uppercase">What is Coffer?</span>

            <p class="mt-2 text-sm text-neutral-300">
                Coffer is the companion app for your tabletop table. A DM stands up a campaign, players roll up characters and join in, the DM stocks and opens shops, and every purchase lands straight in a character's inventory — no more paper ledgers at the table.
            </p>

            <a href="{{ route('guide') }}" wire:navigate class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-300 transition hover:text-brand-200">
                See how it all fits together
                <flux:icon.arrow-right class="size-4" />
            </a>
        </div>
    </div>
    <div class="flex flex-col grow w-full md:w-3/4 max-w-lg bg-white dark:bg-neutral-950">
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

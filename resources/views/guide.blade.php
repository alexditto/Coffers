<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
<div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col gap-10 px-6 py-10 md:py-16">
    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 self-start">
        <span class="flex size-9 items-center justify-center rounded-md">
            <x-app-logo-icon class="size-9 fill-current text-white" />
        </span>
        <span class="text-lg font-semibold text-white">Coffer</span>
    </a>

    <div class="mb-4">
        <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase">How it works</span>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white sm:text-4xl">Everything your table needs, in one place</h1>
        <p class="mt-3 text-base text-neutral-700">
            Coffers keeps a Dungeons &amp; Dragons table's characters, campaigns, shops, and loot in sync — for the DM and every player, on any device.
        </p>
    </div>

    <div class="flex flex-wrap gap-4 items-center justify-center">
        <div class="rounded-2xl border border-neutral-800 bg-neutral-400/60 p-6 max-w-sm">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-200 text-brand-400">
                    <flux:icon.user-group class="size-5" />
                </span>
                <h2 class="text-lg font-bold text-brand-900">DM &amp; player setup</h2>
            </div>
            <p class="mt-3 text-sm text-neutral-700">
                Add friends first, so you always know who's ready to sit down at the table. Whoever creates a campaign becomes its DM; anyone who joins that campaign plays as one of its players. You can be a DM in one campaign and a player in another — Coffers switches the whole app's view to match whichever campaign you have selected.
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-800 bg-neutral-400/60 p-6 max-w-sm">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-200 text-brand-400">
                    <flux:icon.identification class="size-5" />
                </span>
                <h2 class="text-lg font-bold text-brand-900">Character creation</h2>
            </div>
            <p class="mt-3 text-sm text-neutral-700">
                Roll up a character with a name, class, race, level, alignment, and background, plus HP and AC. During play, conditions like poisoned or stunned show up right on the character card so the whole party can see them at a glance. A DM can attach any of your unattached characters to one of their campaigns whenever you're ready to join.
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-800 bg-neutral-400/60 p-6 max-w-sm">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-200 text-brand-400">
                    <flux:icon.map class="size-5" />
                </span>
                <h2 class="text-lg font-bold text-brand-900">Campaign creation</h2>
            </div>
            <p class="mt-3 text-sm text-neutral-700">
                DMs spin up a campaign with a name, description, and image from the DM screen. It's the hub everything else hangs off of — the party roster, the shops, the journal, and the scenes all belong to a single active campaign at a time.
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-800 bg-neutral-400/60 p-6 max-w-sm">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-200 text-brand-400">
                    <flux:icon.building-storefront class="size-5" />
                </span>
                <h2 class="text-lg font-bold text-brand-900">Shop control</h2>
            </div>
            <p class="mt-3 text-sm text-neutral-700">
                DMs stock a shop with items, prices, and quantities, then flip it open, closed, hidden, or back to draft with one tap. The moment a shop opens or closes, every player's screen updates instantly — so a shop the DM just closed can't be mid-purchase a second later.
            </p>
        </div>

        <div class="rounded-2xl border border-neutral-800 bg-neutral-400/60 p-6 max-w-sm">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-200 text-brand-400">
                    <flux:icon.shopping-bag class="size-5" />
                </span>
                <h2 class="text-lg font-bold text-brand-900">Inventory management</h2>
            </div>
            <p class="mt-3 text-sm text-neutral-700">
                Every character carries their own gold and item counts. Spend gold in an open shop and the items land straight in your inventory — no manual bookkeeping, and nothing to reconcile before next session.
            </p>
        </div>
    </div>

    <div class="max-w-lg justify-center flex flex-col items-center gap-4 rounded-2xl border border-neutral-800 bg-neutral-900/60 p-8 text-center mx-auto mt-4 mb-4">
        <h2 class="text-xl font-bold text-white">Ready to gather your party?</h2>

        <div class="flex w-full max-w-xs flex-col gap-3">
            <flux:button :href="route('register')" variant="primary" class="w-full bg-brand-primary hover:bg-brand-secondary hover:border-b-brand-primary">
                {{ __('Create an Account') }}
            </flux:button>

            <flux:button :href="route('login')" variant="primary" class="w-full bg-white text-brand-secondary hover:bg-brand-secondary hover:text-white">
                {{ __('Log in') }}
            </flux:button>
        </div>

        <a href="{{ route('home') }}" wire:navigate class="text-sm text-neutral-400 hover:text-neutral-200">
            Back to home
        </a>
    </div>
</div>

@persist('toast')
<flux:toast.group>
    <flux:toast/>
</flux:toast.group>
@endpersist

@fluxScripts
@vite('resources/js/app.js')

@fluxScripts
</body>
</html>

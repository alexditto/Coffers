<div class="flex flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-surface p-6 shadow-sm sm:p-8 dark:border-line-dark dark:bg-gray-800">
    <div class="mx-auto max-w-2xl text-center">
        <span class="text-[10px] font-bold tracking-widest text-gold-600 uppercase">Roll for initiative</span>
        <h2 class="mt-2 text-2xl font-bold text-content sm:text-3xl dark:text-white">Every campaign needs its Coffers</h2>
        <p class="mt-3 text-sm text-content-muted sm:text-base">
            Coffer is the companion app for your table — gather your party, spin up a campaign, roll up your characters, and let the DM stock the shops. Everything your adventurers earn lands right in their inventory, ready for the next session.
        </p>
    </div>

    <div class="mt-8 grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-3 rounded-xl border border-line bg-canvas p-4 dark:border-line-dark dark:bg-gray-700">
            <div class="flex items-center gap-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-300">1</span>
                <flux:icon.user-group class="size-5 text-brand-600" />
            </div>
            <div>
                <div class="text-sm font-bold text-content dark:text-white">Gather your party</div>
                <p class="mt-1 text-xs text-content-muted dark:text-gray-400">Add friends so you always know who's ready to sit down at the table.</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-xl border border-line bg-canvas p-4 dark:border-line-dark dark:bg-gray-700">
            <div class="flex items-center gap-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-300">2</span>
                <flux:icon.map class="size-5 text-brand-600" />
                <flux:icon.identification class="size-5 text-brand-600" />
            </div>
            <div>
                <div class="text-sm font-bold text-content dark:text-white">Create a campaign or build a character</div>
                <p class="mt-1 text-xs text-content-muted dark:text-gray-400">Take the DM's chair and build a campaign, or roll up a character and join the party as a player.</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-xl border border-line bg-canvas p-4 dark:border-line-dark dark:bg-gray-700">
            <div class="flex items-center gap-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900 dark:text-brand-300">3</span>
                <flux:icon.building-storefront class="size-5 text-brand-600" />
            </div>
            <div>
                <div class="text-sm font-bold text-content dark:text-white">Open the shops</div>
                <p class="mt-1 text-xs text-content-muted dark:text-gray-400">DMs stock a shop with loot, then flip it open or closed with one tap.</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-xl border border-line bg-canvas p-4 dark:border-line-dark dark:bg-gray-700">
            <div class="flex items-center gap-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gold-100 text-xs font-bold text-gold-700 dark:bg-gold-900 dark:text-gold-900">4</span>
                <flux:icon.shopping-bag class="size-5 text-gold-600 dark:text-gold-400" />
            </div>
            <div>
                <div class="text-sm font-bold text-content dark:text-white">Fill the coffers</div>
                <p class="mt-1 text-xs text-content-muted dark:text-gray-400">Players spend their gold in open shops and watch the loot land in their inventory.</p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex flex-col items-center justify-center gap-2 sm:flex-row">
        <flux:button :href="route('friends')" wire:navigate variant="primary" icon="user-group">
            Add a friend
        </flux:button>

        <flux:button :href="route('campaign-builder')" wire:navigate variant="ghost" icon="map">
            Start a campaign
        </flux:button>
    </div>
</div>

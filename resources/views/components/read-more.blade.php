<ui-disclosure {{ $attributes->class('block') }}>
    <div class="group/read-more">
        <p class="text-sm text-neutral-700 line-clamp-3 in-data-open:line-clamp-none dark:text-neutral-300">
            {{ $slot }}
        </p>
        <button type="button"
                class="mt-1 text-xs font-semibold text-brand-700 hover:underline dark:text-brand-400">
            <span class="group-data-open/read-more:hidden">Read more</span>
            <span class="hidden group-data-open/read-more:inline">Show less</span>
        </button>
    </div>
</ui-disclosure>

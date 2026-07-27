@props([
    'model',
    'options',
    'selected' => [],
    'label' => 'Conditions',
])

@php
    $selectedNames = $options->whereIn('id', $selected)->pluck('name');
@endphp

<flux:field>
    <flux:label>{{ $label }}</flux:label>

    <flux:dropdown position="bottom" align="start" class="block w-full">
        <button type="button" class="flex w-full items-center justify-between gap-2 rounded-xl border border-line bg-surface-subtle px-3 py-2 text-left text-sm transition hover:border-brand-300">
            <span class="truncate {{ $selectedNames->isEmpty() ? 'text-content-muted' : 'text-content' }}">
                {{ $selectedNames->isEmpty() ? 'None' : $selectedNames->implode(', ') }}
            </span>
            <flux:icon.chevron-down class="size-3.5 shrink-0 text-content-muted" />
        </button>

        <flux:menu keep-open class="max-h-64 w-64 overflow-y-auto">
            <flux:menu.checkbox.group wire:model="{{ $model }}">
                @foreach ($options as $option)
                    <flux:menu.checkbox value="{{ $option->id }}" wire:key="condition-option-{{ $option->id }}">
                        {{ $option->name }}
                    </flux:menu.checkbox>
                @endforeach
            </flux:menu.checkbox.group>
        </flux:menu>
    </flux:dropdown>
</flux:field>

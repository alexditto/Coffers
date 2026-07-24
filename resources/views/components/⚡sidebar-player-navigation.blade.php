<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<flux:sidebar.group :heading="__($campaignName)" class="grid">
    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                       wire:navigate>
        {{ __('Character') }}
    </flux:sidebar.item>
    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                       wire:navigate>
        {{ __('Shops') }}
    </flux:sidebar.item>
    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                       wire:navigate>
        {{ __('Inventory') }}
    </flux:sidebar.item>
    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                       wire:navigate>
        {{ __('Journal') }}
    </flux:sidebar.item>
</flux:sidebar.group>

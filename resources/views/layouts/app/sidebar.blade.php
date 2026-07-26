<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-800 flex flex-col lg:flex-row">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('friends')" :current="request()->routeIs('friends')" wire:navigate>
                        {{ __('Friends') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('campaign-builder')" :current="request()->routeIs('campaign-builder')" wire:navigate>
                        {{ __('Manage Campaigns') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="identification" :href="route('character-builder')" :current="request()->routeIs('character-builder')" wire:navigate>
                        {{ __('Manage Characters') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
            <livewire:sidebar-dynamic-navigation />

            <flux:spacer />

            <flux:sidebar.nav>

            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile Top Bar -->
        <flux:header class="lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <livewire:campaign-selector-banner/>
        </flux:header>

        <div class="pb-15 lg:pb-0 grow">
            {{ $slot }}
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav class="fixed inset-x-0 bottom-0 z-40 flex border-t border-line bg-surface lg:hidden">
            <flux:dropdown position="top" align="start" class="flex-1">
                <button type="button" class="flex w-full flex-col items-center justify-center gap-1 py-2 text-content-faint">
                    <flux:icon.squares-2x2 class="size-5" />
                    <span class="text-[10px] font-semibold">More</span>
                </button>

                <flux:menu>
                    <flux:menu.item icon="home" :href="route('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:menu.item>
                    <flux:menu.item icon="user-group" :href="route('friends')" wire:navigate>
                        {{ __('Friends') }}
                    </flux:menu.item>
                    <flux:menu.item icon="map" :href="route('campaign-builder')" wire:navigate>
                        {{ __('Manage Campaigns') }}
                    </flux:menu.item>
                    <flux:menu.item icon="identification" :href="route('character-builder')" wire:navigate>
                        {{ __('Manage Characters') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <livewire:mobile-bottom-navigation />

            <flux:dropdown position="top" align="end" class="flex-1">
                <button type="button" class="flex w-full flex-col items-center justify-center gap-1 py-2 text-content-faint">
                    <flux:avatar size="xs" circle :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                    <span class="text-[10px] font-semibold">Profile</span>
                </button>

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        @vite('resources/js/app.js')
    </body>
</html>

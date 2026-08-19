<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
<div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-10 px-6 py-10 md:py-16">
    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 self-start">
        <span class="flex size-9 items-center justify-center rounded-md">
            <x-app-logo-icon class="size-9 fill-current text-white"/>
        </span>
        <span class="text-lg font-semibold dark:text-white">Coffer</span>
    </a>

    <div class="mb-4">
        <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase">Hire me</span>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white sm:text-4xl">Nerdy software developer, available
            for hire</h1>
        <p class="mt-3 text-base text-neutral-700 dark:text-neutral-400 sm:text-lg">
            I built Coffers, along with the projects below. Take a look around, then reach out on GitHub, LinkedIn, or
            grab my resume.
        </p>

        <div class="mt-5 flex flex-wrap gap-3">
            <flux:button href="https://github.com/alexditto" target="_blank" rel="noopener" variant="ghost"
                         icon="code-bracket">
                GitHub
            </flux:button>

            <flux:button href="https://www.linkedin.com/in/alexander-ditto-0a69aa141/" target="_blank" rel="noopener"
                         variant="ghost" icon="link">
                LinkedIn
            </flux:button>

            <flux:button href="https://github.com/alexditto/Resume" target="_blank" rel="noopener" variant="ghost"
                         icon="document-text">
                Resume
            </flux:button>
        </div>
    </div>

    <div class="bg-neutral-100 rounded-2xl p-8 text-center">
        <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase">Skills</span>
        <h2 class="mt-2 text-lg font-bold text-black dark:text-white">What I Work With</h2>

        <div class="mt-4 flex flex-col gap-4 text-left sm:mx-auto sm:max-w-2xl">
            <div>
                <span class="text-xs font-semibold text-neutral-500 uppercase dark:text-neutral-400">Backend & APIs</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <flux:badge size="sm">PHP</flux:badge>
                    <flux:badge size="sm">Laravel</flux:badge>
                    <flux:badge size="sm">Livewire</flux:badge>
                    <flux:badge size="sm">Filament</flux:badge>
                    <flux:badge size="sm">Node.js / Express</flux:badge>
                    <flux:badge size="sm">Go</flux:badge>
                    <flux:badge size="sm">Django</flux:badge>
                    <flux:badge size="sm">REST & OpenAPI</flux:badge>
                </div>
            </div>

            <div>
                <span class="text-xs font-semibold text-neutral-500 uppercase dark:text-neutral-400">Frontend</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <flux:badge size="sm">React</flux:badge>
                    <flux:badge size="sm">Next.js</flux:badge>
                    <flux:badge size="sm">Tailwind CSS</flux:badge>
                </div>
            </div>

            <div>
                <span class="text-xs font-semibold text-neutral-500 uppercase dark:text-neutral-400">Data & Infrastructure</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <flux:badge size="sm">MySQL</flux:badge>
                    <flux:badge size="sm">Elasticsearch</flux:badge>
                    <flux:badge size="sm">Google Maps Platform</flux:badge>
                    <flux:badge size="sm">SuiteScript & SuiteQL</flux:badge>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-neutral-100 rounded-2xl p-8 text-center">
        <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase">Projects</span>
        <h2 class="mt-2 text-lg font-bold text-black dark:text-white">Selected Work</h2>
        <div class="mt-4 flex flex-wrap gap-4 items-center justify-center">
            <div class="max-w-xs overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-400/60">
                <img src="{{ asset('img/benchmark-5.png') }}" alt="Mead Makers screenshot"
                     class="h-40 w-full object-cover"/>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-brand-900 dark:text-brand-400">Mead Makers</h3>
                    <p class="mt-3 text-sm text-neutral-700 dark:text-neutral-300">
                        A mead-brewing tracker — recipes, batches, and a bit of a social layer for friends — built
                        primarily as a demonstration of a full Node/Express + MySQL + Next.js stack rather than as a
                        polished product.
                    </p>
                </div>
            </div>

            <div class="max-w-xs overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-400/60">
                <img src="{{ asset('img/benchmark-2.png') }}"
                     alt="Portals, Integrations, and more built with Laravel screenshot"
                     class="h-40 w-full object-cover"/>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-brand-900 dark:text-brand-400">Portals, Integrations, and more
                        built with Laravel</h3>
                    <p class="mt-3 text-sm text-neutral-700 dark:text-neutral-300">
                        Led the Customer, Franchise, and Infinite Pipeline portals using Laravel, Livewire, Filament,
                        MySQL, and versioned APIs. In Infinite Pipeline, appointment-created events trigger listeners
                        and observers that process payments and notify users without routine human intervention.
                    </p>
                </div>
            </div>

            <div class="max-w-xs overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-400/60">
                <img src="{{ asset('img/benchmark-3.png') }}"
                     alt="SuiteScript, SuiteQL, and Google Maps Platform screenshot"
                     class="h-40 w-full object-cover"/>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-brand-900 dark:text-brand-400">SuiteScript, SuiteQL, and Google
                        Maps Platform</h3>
                    <p class="mt-3 text-sm text-neutral-700 dark:text-neutral-300">
                        Built and re-architected a reusable SuiteScript, SuiteQL, and Google Maps platform for
                        marketing, site visits, franchise deployment, and outside-sales planning.
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-4 items-center justify-center">
            <div class="max-w-xs overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-400/60">
                <img src="{{ asset('img/benchmark-1.png') }}" alt="Operations Field App screenshot"
                     class="h-40 w-full object-cover"/>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-brand-900 dark:text-brand-400">Operations Field App</h3>
                    <p class="mt-3 text-sm text-neutral-700 dark:text-neutral-300">
                        Created a React, Tailwind CSS, and Google Maps field application that surfaces nearby records,
                        captures activity, creates leads, and standardizes operational reporting.
                    </p>
                </div>
            </div>

            <div class="max-w-xs overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-400/60">
                <img src="{{ asset('img/benchmark-6.png') }}"
                     alt="OpenMimic screenshot"
                     class="h-40 w-full object-cover"/>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-brand-900 dark:text-brand-400">OpenMimic</h3>

                    <x-read-more class="mt-3">
                        Feed it JSON. Loot OpenAPI. OpenAPI Example Generator is a self-contained Django application
                        that helps developers create valid OpenAPI 3.1 documentation from request and response
                        examples. Rather than requiring developers to write an OpenAPI YAML document manually, the
                        application allows them to describe an API operation through a guided interface and
                        automatically converts that information into a validated specification. The application does
                        not connect to or execute requests against live APIs. Developers provide the relevant
                        information directly, keeping the initial product safe, predictable, and focused on
                        documentation generation.
                    </x-read-more>
                </div>
            </div>

            <div class="max-w-xs overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-400/60">
                <img src="{{ asset('img/benchmark-4.png') }}"
                     alt="GeoElastic screenshot"
                     class="h-40 w-full object-cover"/>

                <div class="p-6">
                    <h3 class="text-lg font-bold text-brand-900 dark:text-brand-400">GeoElastic</h3>
                    <x-read-more class="mt-3">
                        GeoElastic is a Go API that takes a business's identifying details — name, address, phone
                        number, location — and searches an Elasticsearch index of business/geo records to figure out
                        whether it's an exact match, a probable ("fuzzy") match, or no match at all. The goal is a
                        reusable building block for cross-referencing and de-duplicating business records that live in
                        two different systems, where the same business might be recorded with slightly different
                        spellings, formatting, or partial information in each.
                    </x-read-more>
                </div>
            </div>
        </div>
    </div>

    <div
        class="max-w-lg justify-center flex flex-col items-center gap-4 rounded-2xl border border-neutral-800 bg-neutral-900/60 p-8 text-center mx-auto mt-4 mb-4">
        <h2 class="text-xl font-bold text-white">Let's build something together</h2>

        <div class="flex flex-wrap justify-center gap-3">
            <flux:button href="https://github.com/alexditto" target="_blank" rel="noopener" variant="primary"
                         class="bg-brand-primary hover:bg-brand-secondary hover:border-b-brand-primary">
                GitHub
            </flux:button>

            <flux:button href="https://www.linkedin.com/in/alexdittodev/" target="_blank" rel="noopener"
                         variant="primary"
                         class="bg-white text-brand-secondary hover:bg-brand-secondary hover:text-white">
                LinkedIn
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
</body>
</html>

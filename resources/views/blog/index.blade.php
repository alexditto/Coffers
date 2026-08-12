@php
    // To add a new post: create resources/views/blog/posts/{slug}.blade.php,
    // then add an entry here so it shows up in the list.
    $posts = [
        [
            'slug' => 'make-dev-cry',
            'title' => 'Hey Claude, How Do I Make a Developer Cry?',
            'excerpt' => 'So, our solution cannot be hoping for a good guy in the crowd of bad guys.',
            'date' => '2026-08-12',
            'image' => 'make-dev-cry.png',
        ],
        [
            'slug' => 'models-migrations-mimicry',
            'title' => 'How Are You Using AI?',
            'excerpt' => 'Everyone is hype, but no one is strategy. Introducing the Models, Migrations, Mimicry method.',
            'date' => '2026-08-11',
            'image' => 'models-migrations-mimicry.png',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', [
        'title' => 'Blog',
        'description' => 'Updates, design notes, and behind-the-scenes posts about building Coffers.',
        'image' => asset('img/'.$posts[0]['image']),
    ])
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
        <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase">Blog</span>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white sm:text-4xl">Notes from the Coffers table</h1>
        <p class="mt-3 text-base text-neutral-700">
            Updates, design notes, and behind-the-scenes posts about building Coffers.
        </p>
    </div>

    <div class="flex flex-col gap-4">
        @foreach ($posts as $post)
            <a
                href="{{ route('blog.show', $post['slug']) }}"
                wire:navigate
                class="flex justify-between gap-4 rounded-2xl border border-neutral-800 bg-neutral-400/60 p-6 transition hover:border-brand-400"
            >
                <div>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ \Illuminate\Support\Carbon::parse($post['date'])->format('F j, Y') }}
                    </span>
                    <h2 class="mt-1 text-lg font-bold text-brand-900 dark:text-brand-400">{{ $post['title'] }}</h2>
                    <p class="mt-2 text-sm text-neutral-700 dark:text-neutral-300">{{ $post['excerpt'] }}</p>
                </div>
                <img
                    src="{{ asset('img/'.$post['image']) }}"
                    alt="{{ $post['title'] }}"
                    class="h-20 w-28 shrink-0 rounded-xl border border-neutral-800 object-cover sm:h-24 sm:w-36"
                />
            </a>
        @endforeach
    </div>

    <a href="{{ route('home') }}" wire:navigate class="text-sm text-neutral-400 hover:text-neutral-200">
        Back to home
    </a>
</div>

@persist('toast')
<flux:toast.group>
    <flux:toast/>
</flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>

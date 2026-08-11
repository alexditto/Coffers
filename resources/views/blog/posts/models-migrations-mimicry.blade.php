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

    <article>

        <span class="text-xs text-neutral-500 dark:text-neutral-400">August 11, 2026</span>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white sm:text-4xl">How Are You Using AI?</h1>
        <img
            src="{{ asset('img/models-migrations-mimicry.png') }}"
            alt="A small robot wizard casts a code spell at a mimic treasure chest, surrounded by scrolls of diagrams, in a torch-lit stone room."
            class="mt-8 w-full rounded-2xl border border-neutral-800"
        />

        <div class="mt-6 flex flex-col gap-4 text-base text-neutral-700 dark:text-neutral-300">
            <p>It&rsquo;s become the &ldquo;What does &lsquo;state&rsquo; mean to you?&rdquo; of interviews.</p>

            <p>The reason is clear: We are all using AI, we all need to understand AI and how to use or maintain it, and we are all just making it up every time someone asks us.</p>

            <p>The change in how software developers are engaging with AI is best represented by the change from Laracon 2025 to Laracon 2026. Doom and gloom was the best description of many of the talks in 2025, as speakers tried to talk developers off the roofs of their buildings and back to their keyboards. This year, however, the tone was absolutely optimistic, assuming that AI would empower developers to ship more, faster, and with less overhead.</p>

            <p>That change makes sense when you look at the larger community. AI development is no longer an opt-in tool; it&rsquo;s now a primary skill.</p>

            <blockquote class="border-l-2 border-brand-400 pl-4 text-neutral-600 italic dark:text-neutral-400">
                &ldquo;Okay, so I should use AI. Thanks, Alex. I will file you away with every other voice shouting into the void.&rdquo;
            </blockquote>

            <p>Ah, yes. You should use AI. But how should you use AI?</p>

            <p>It&rsquo;s the obvious question that many people are asking, but clear explanations of how to systematically use AI in development are oddly lacking. Much like the fitness craze, everyone is hype, but no one is strategy.</p>

            <p>So, let me offer a strategy.</p>

            <p>I have developed an approach to using AI, particularly tailored to Laravel, that has reduced my token usage, improved output, and shortened development time. I would like to introduce you to the &ldquo;Models, Migrations, Mimicry&rdquo; method.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">Look at that alliteration. I did go to seminary, after all.</p>
        </div>

        <div class="mt-8 flex flex-col gap-4 text-base text-neutral-700 dark:text-neutral-300">
            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Models</h2>

            <p>Models are the foundation of the Laravel system and often a cornerstone of object-oriented applications. They define the structure of the application, establish its attributes or properties, and, in Laravel, define the Eloquent relationships between different models.</p>

            <p>These features&mdash;structure, attributes, and relationships&mdash;all represent high-level decisions that will dramatically affect the direction of the application.</p>

            <p>But they aren&rsquo;t just impactful. Models are better defined in code than in speech.</p>

            <p>Telling an AI agent that &ldquo;the Post model should connect to the Like model and connect to the User model so that the user can like a post but then unlike the post in the future if they decide they don&rsquo;t like it&rdquo; is trash.</p>

            <p>It&rsquo;s bad.</p>

            <p>It doesn&rsquo;t clearly articulate how the models need to work together, and it doesn&rsquo;t really indicate how those relationships will function within the application. The agent has to infer that the relationship between Post and Like is one-to-one, but it might actually be polymorphic in your application if you intend for users to be able to like videos, images, or people in the future.</p>

            <p>When that information is conveyed in code, however, it is clear to the agent. Eliminating the guesswork surrounding the relationships between these models allows the agent to clearly follow your lead.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Migrations</h2>

            <p>Migrations are very Laravel-centric, but the concept can be found in other languages and database systems as well.</p>

            <p>In Laravel, migrations are the programmatic way of setting up your database and establishing any additions, changes, or removals. Whether it is Alembic with SQLAlchemy, Goose in Go, or index mappings in Elasticsearch, the important questions are: Where am I setting up my database, and what fields am I using?</p>

            <p>This stage is important for two reasons.</p>

            <p>First, it helps define more of the structure of your models, particularly the dynamic elements that need to be saved long-term.</p>

            <p>Second, it gives you the ability to make smart optimization decisions. AI will use <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">longText</code> when it does not need it. It will rarely reach for <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">char</code>, <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">unsignedTinyInteger</code>, or <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">nullableUuidMorphs</code>. Some of these column-type decisions can have a dramatic impact on the overall performance of your application.</p>

            <p>Switching to a new column type once your application is already in development&mdash;or, worse, in production&mdash;could result in lost data, downstream bugs, or even catastrophic application failure.</p>

            <p>Save yourself hours of computation and headaches by clearly articulating the database structure in code.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Mimicry</h2>

            <p>Mimicry is the final and most pivotal aspect of this approach.</p>

            <p>Mimicry means providing clear, stylized examples of how you want the final application to look. You do not need a wireframe for every page, but a few wireframes are ideal for helping the agent understand which Tailwind classes and visual patterns you intend to use.</p>

            <p>Even without wireframes, a detailed brand guide, a couple of screenshots, or even a good hand-drawn picture will help your model understand what the final product should look like.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">Then, as all wizards long for, it&rsquo;s magic time.</p>

            <p>In Laravel, make sure you run Boost, initialize Claude, and start slinging code. Instruct your agent to read your models and migrations to get a feel for the structure of the project. Tell it to review the folder where you have saved your wireframes so it can understand your style and UI/UX direction.</p>

            <p>Then, tell it to build.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">Cast your spells. Watch the magic unfold.</p>

            <p>You will find that the resulting code is cleaner, more focused, and produced faster.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">I&rsquo;m Convinced. Alex is a Genius, and Handsome.</h2>

            <p>We need methods for approaching AI development.</p>

            <p>AI tools are wonderful for all the reasons you have already heard a thousand times. Using AI efficiently and methodically will help increase those moments when you love Claude and reduce those moments when you hate Grok.</p>

            <p>This method is my starting point. I hope it can also serve as a foundational idea as you develop your own method for working with AI.</p>
        </div>
    </article>

    <a href="{{ route('blog.index') }}" wire:navigate class="text-sm text-neutral-400 hover:text-neutral-200">
        Back to blog
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

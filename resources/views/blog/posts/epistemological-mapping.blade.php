<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', [
        'title' => 'Let’s Get Dirty with Some Epistemological Mapping',
        'description' => 'A simple four-domain method for mapping what you know—demoed on Eloquent’s often-overlooked isDirty() method.',
        'image' => asset('img/epistemological-mapping.png'),
        'ogType' => 'article',
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

    <article>
        <span class="text-xs text-neutral-500 dark:text-neutral-400">August 17, 2026</span>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white sm:text-4xl">Let&rsquo;s Get Dirty with Some Epistemological Mapping</h1>

        <img
            src="{{ asset('img/epistemological-mapping.png') }}"
            alt="A philosopher robot draws a four-quadrant map on a chalkboard labeled Know, Teach, Pretty Sure, and Don&rsquo;t Know, beside a glowing Eloquent model diagram."
            class="mt-8 w-full rounded-2xl border border-neutral-800"
        />

        <div class="mt-6 flex flex-col gap-4 text-base text-neutral-700 dark:text-neutral-300">
            <p class="text-neutral-500 italic dark:text-neutral-400">There is a title!</p>

            <p>Before I started working in software development, I was fixated on philosophy. My interest in philosophy began in its most primitive form in high school, when I was exposed to apologetics, but it was not until I started my undergraduate studies that I took my first true philosophy class.</p>

            <p>Introduction to philosophy courses are among the most important classes students can take outside their disciplines. Even a brief introduction can expose you to concepts that reshape the way you approach ethics, being, learning, and knowledge.</p>

            <p>Speaking of knowledge, let&rsquo;s talk about <b>epistemological mapping.</b></p>

            <p><b>Epistemological mapping</b> is the practice of conceptualizing knowledge through some type of visualization for the purpose of clarifying a topic, identifying authoritative foundations, establishing plausibility structures, and revealing shortcomings or hidden biases.</p>

            <p>Put simply, a good knowledge map will help you understand what you know, why you know it, what knowing it means, and what you don&rsquo;t know about it.</p>

            <p>Epistemic maps can help you not only know about a topic or category but also understand why and how you know what you know&hellip; you know?</p>

            <p>If you want to become a more confident developer while building resilience against impostor syndrome, you need to get good at developing epistemic maps.</p>

            <p>I have developed a simplified, four-domain approach to mapping development concepts that is easy to remember and incorporate into your personal study. If you use these four domains to organize your thoughts, you will become better informed, have an easier time teaching others, and grow more confident in your knowledge.</p>

            <p>The four domains are:</p>

            <ul class="list-disc pl-6">
                <li>What do I know?</li>
                <li>What can I teach?</li>
                <li>What am I pretty sure about?</li>
                <li>What do I know I don&rsquo;t know?</li>
            </ul>

            <p>Let&rsquo;s put these into practice and see how we might develop a map of one of Laravel&rsquo;s often-overlooked but important methods: <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">isDirty()</code>.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">I chose it for academic reasons. That&rsquo;s all.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">What Do I Know?</h2>

            <p>This domain should always be your starting point.</p>

            <p>Organize your thoughts in whatever way works best for your mind, whether that means bullet points, single-word thought bubbles, or long paragraphs. The main point of this domain is to begin by developing a clear picture of what you know.</p>

            <p>And don&rsquo;t worry&mdash;at this point&mdash;about being right.</p>

            <p>It is actually helpful to include something in your &ldquo;Know&rdquo; domain if you currently believe it to be true. Discovering later that your understanding was limited or wrong will benefit your epistemic map because it may reveal other assumptions or misunderstandings.</p>

            <blockquote class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 not-italic text-neutral-700 dark:border-brand-800/60 dark:bg-brand-950/40 dark:text-neutral-300">
                <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase dark:text-brand-400">Mapping isDirty()</span>

                <p><code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">isDirty()</code> is a method available on Laravel Eloquent models. Eloquent models maintain both their current attributes and a copy of their original attributes. Laravel compares these values to determine whether an attribute has changed.</p>

                <p>When a model is retrieved from the database, its current and original attributes match, so it is clean. If one of its attributes is changed, that attribute&mdash;and therefore the model&mdash;is dirty. Once the model is saved, its current state becomes its original state, and the model is no longer dirty.</p>
            </blockquote>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">What Can I Teach?</h2>

            <p>I find this to be the best domain to pursue next.</p>

            <p>This is where knowledge and confidence start to separate. Look at the previous domain and determine what you can expound upon, but only allow statements into this domain when you are confident they are true.</p>

            <p>Now, in reality, you could still be wrong, and that&rsquo;s fine. No one knows a topic perfectly. However, you should have some skin in the game on these points. I will sometimes ask myself, &ldquo;Would I bet $100 that I am right?&rdquo;</p>

            <blockquote class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 not-italic text-neutral-700 dark:border-brand-800/60 dark:bg-brand-950/40 dark:text-neutral-300">
                <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase dark:text-brand-400">Mapping isDirty()</span>

                <p><code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">isDirty()</code> is particularly useful before a model is saved, including within an observer&rsquo;s <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">saving</code> or <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">updating</code> methods. An observer listens for Eloquent model events such as <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">created</code>, <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">updated</code>, <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">deleted</code>, and <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">restored</code>.</p>

                <p>You can pass a specific attribute&mdash;or an array of attributes&mdash;to <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">isDirty()</code> to determine whether any of those values have changed.</p>

                <pre class="overflow-x-auto rounded-lg border border-neutral-800 bg-neutral-900/90 p-4 font-mono text-sm text-brand-200"><code>if ($user->isDirty('email')) {
    // The email attribute has changed but has not yet been saved.
}</code></pre>

                <p>After the model has been saved, <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">isDirty()</code> will return false because the original attributes have been synchronized with the model&rsquo;s current state. At that point, you can use <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">wasChanged()</code> to determine whether an attribute changed during the most recent save.</p>
            </blockquote>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">What Am I Pretty Sure About?</h2>

            <p>This domain is where your assertions should begin to split according to their level of confidence. The great thing about this domain is that you can speculate with the total freedom to be wrong. Include random thoughts, details you think you heard somewhere, and ideas you know you wouldn&rsquo;t be able to defend in the slightest.</p>

            <p>The strength of this domain is twofold.</p>

            <p>First, it establishes weaker areas in your overall knowledge that you can tighten up later, hopefully moving them into the first or second domain.</p>

            <p>Second, identifying knowledge in this area helps you properly represent your understanding of the topic as a whole.</p>

            <p>Remember, the goal of understanding a topic is not to possess absolute knowledge. Often, knowing what you know&mdash;and knowing what you don&rsquo;t&mdash;offers a commanding authority on the subject.</p>

            <blockquote class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 not-italic text-neutral-700 dark:border-brand-800/60 dark:bg-brand-950/40 dark:text-neutral-300">
                <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase dark:text-brand-400">Mapping isDirty()</span>

                <p>I think <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">isDirty()</code> comes from somewhere inside the Eloquent model&rsquo;s attribute-handling system, but I am not actually sure how the sausage is made on that one.</p>

                <p>I am also pretty sure that if an attribute was previously null and then changed to another value, it would still be considered dirty.</p>

                <p>I think observers are triggered by model operations such as saving, updating, or deleting unless you use something like <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">saveQuietly()</code>. I am less certain about what happens when one model is saved because of an update to another model. I think you could chain observers together that way, but that sounds awful.</p>
            </blockquote>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">What Do I Know I Don&rsquo;t Know?</h2>

            <p>And now we have arrived at the most important domain for growth and confidence. The reality is that you can never know what you don&rsquo;t know you don&rsquo;t know. However, there are times when you recognize clear gaps in your knowledge.</p>

            <p>After mapping the third domain, you may discover questions that can help populate this one. The aim of this domain&mdash;besides developing academic humility&mdash;should be to define a direction for further exploration and mastery of the topic.</p>

            <p>This is also the time to be brutally honest, even if it makes you feel dumb.</p>

            <p>Use being dumb as a superpower. Being dumb is the first step toward becoming slightly less dumb.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400 text-right"> - That feels like a quote. I don&rsquo;t know&mdash;Socrates. Why not?</p>

            <blockquote class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 not-italic text-neutral-700 dark:border-brand-800/60 dark:bg-brand-950/40 dark:text-neutral-300">
                <span class="text-[10px] font-bold tracking-widest text-brand-700 uppercase dark:text-brand-400">Mapping isDirty()</span>

                <p>I don&rsquo;t know how observers actually work under the hood.</p>

                <p>I use them frequently, and I know that if I manually change an entry in the database, the observer does not activate. But does every Eloquent model have an interception point for observers? Is it part of the <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">save()</code> method?</p>

                <p>And how do PHP attributes actually work with something like this? <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-sm text-brand-300">#[ObservedBy([UserObserver::class])]</code> What is that actually doing?</p>

                <p>Laravel is great when it just works&mdash;but why does it just work?</p>
            </blockquote>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Map Complete</h2>

            <p>And there you have it. You now have a well-formed mental&mdash;or epistemic&mdash;map of a topic.</p>

            <p>Take a look at it. Review it.</p>

            <p>Does it look the way you assumed it would when you started the exercise? Is one domain a bit skinnier than you wanted it to be, or are you impressed by how much you are already willing to teach?</p>

            <p>The main aim of the exercise is to take stock of where you are at this moment. Once you have done that, the map can provide a clear roadmap for the areas you want to strengthen and expand in the future. You can try to move more items from domain three into domain two or check off items in domain four. Better still, expand domain four. Identify more aspects of the topic that you don&rsquo;t understand at all. Use this exercise periodically, and you may find that your next blog entry, TikTok video&mdash;are they videos? I am so old&mdash;or conference talk comes more easily and with greater confidence.</p>
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

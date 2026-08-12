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
        <span class="text-xs text-neutral-500 dark:text-neutral-400">August 12, 2026</span>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white sm:text-4xl">Hey Claude, How Do I Make a Developer Cry?</h1>

        <img
            src="{{ asset('img/make-dev-cry.png') }}"
            alt="A cheerful robot proudly shows a crying developer a screen that reads DROP TABLE user, while error icons explode across the monitors behind them."
            class="mt-8 w-full rounded-2xl border border-neutral-800"
        />

        <div class="mt-6 flex flex-col gap-4 text-base text-neutral-700 dark:text-neutral-300">
            <p>By this point, you may have already come across the story of Andrew in Australia accidentally hacking his gym&rsquo;s booking system.</p>

            <p>It&rsquo;s a fantastic story that will likely play out a thousand times over the next few years&mdash;across other applications, other websites, and, unfortunately, with how cheap these processes are becoming, your servers as well.</p>

            <p>There are some key points to notice in this story. So, what happened?</p>

            <p>Andrew says that he asked an agent running as his personal assistant to sign him up for a gym class. From what I can glean, he was already a member of the gym, but his gym has special classes that are scheduled months in advance and only open for registration a limited time beforehand. Some gym members might also be waitlisted. So, signing up early makes you more likely to get into that hot yoga class you have been craving.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">Hot yoga is still a thing, right?</p>

            <p>Andrew gave his agent an innocuous task: &ldquo;Sign me up for a class.&rdquo;</p>

            <p>As Andrew recalls the interaction, the agent came back and reported that it had signed him up for a class much further in advance than the website intended to allow. Furthermore, he was told that he was number four on the waitlist for another class.</p>

            <p>He responded with another prompt: &ldquo;Can you move me up the list?&rdquo;</p>

            <p>That was the command that caused his agent to wreck a developer&rsquo;s week.</p>

            <blockquote class="border-l-2 border-brand-400 pl-4 text-neutral-600 italic dark:text-neutral-400">
                &ldquo;The API has zero authorisation checks on cancelling other people&rsquo;s reservations &hellip; I tested this with the person in waitlist position #1&mdash;and it actually went through. So you&rsquo;ve moved from #4 to #3 already.&rdquo;
            </blockquote>

            <p class="text-neutral-500 italic dark:text-neutral-400">And somewhere, a developer had a panic attack.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">So, What Went Wrong?</h2>

            <p>The problem is pretty clear on the gym&rsquo;s side: It had an API endpoint that removed users from the waitlist&mdash;likely through a simple delete request&mdash;that did not require the correct level of authorization.</p>

            <p>This endpoint is often described as having no authorization checks at all, but I suspect that this is not entirely accurate. I suspect that the API enforced general authorization, but did not scope requests based on the user.</p>

            <p>A standard approach to this type of request would allow a user to delete only their own waitlist entry, while an administrator could delete any entry. This mistake is an easy miss for any developer.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">How Do We Fix It?</h2>

            <p>In many ways, the gym is about as lucky as it could have been.</p>

            <p>The person who hacked it is a good guy. He didn&rsquo;t pivot and start stealing free classes. He didn&rsquo;t delete every user from every waitlist. He didn&rsquo;t take any number of actions that could have caused direct financial or reputational harm.</p>

            <p>Instead, he sent the company a message explaining the vulnerability.</p>

            <p>He&rsquo;s a great guy. Most agents aren&rsquo;t.</p>

            <p>While I believe in the basic goodness of most people, remember that bots are a numbers game. Good people don&rsquo;t spin up 100 agents to try to break your application, but bad guys do. They will spin up thousands.</p>

            <p>So, our solution cannot be hoping for a good guy in the crowd of bad guys.</p>

            <p>There are three practices you can implement today, near the end of your development cycle, that will help harden your application against AI agents.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Have the Clankers Check for Vulnerabilities</h2>

            <p class="text-neutral-500 italic dark:text-neutral-400">Think about it: It&rsquo;s their fault we are in this mess.</p>

            <p>Sure, we all have that old API project we aren&rsquo;t super proud of. We are all just hoping that some poor junior developer out there is eventually tasked with cleaning up the service we spun up and shipped with one too few reviews.</p>

            <p>Historically, that has been fine&hellip; enough.</p>

            <p>It is a new phenomenon that every user has accidentally become a script kiddie as their agents ruthlessly attack your processes for every vulnerability. We need to respond accordingly.</p>

            <p>Make AI-assisted security review a standard part of your development workflow. Just like automated testing, you can prompt your agent:</p>

            <blockquote class="rounded-xl border border-neutral-800 bg-neutral-900/80 p-4 font-mono text-sm text-brand-200">
                &ldquo;Scan my application for vulnerabilities that a user might exploit to access unauthorized processes or perform unintended actions. Check my middleware, authorization rules, and API endpoints. Report any potential vulnerabilities to me without modifying the application.&rdquo;
            </blockquote>

            <p>This prompt can help harden an application because it directs the agent to inspect the places where vulnerabilities are common:</p>

            <ul class="list-disc pl-6">
                <li>Who has access?</li>
                <li>How are they getting it?</li>
                <li>What are they able to do?</li>
            </ul>

            <p>An agent can be effective at scanning these parts of your application, with the added benefit of having a like mind inspect your code from the perspective of an agent trying to break it.</p>

            <p>This should be one layer alongside automated security testing, dependency scanning, and human review&mdash;not the entire security process.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">Clanker versus clanker.</p>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Delete Code</h2>

            <p>Agents are great at producing a lot of code quickly, but this also creates code that is duplicated, deprecated, or unnecessary.</p>

            <p>Reducing complexity and overhead is a prime concern for developers. We are taught over and over:</p>

            <p class="text-lg font-bold text-brand-300">Clean code. Clean code. Clean code.</p>

            <p>For good reason. Clean code makes a codebase easier to manage and often reduces or eliminates vulnerabilities. Reducing the size and complexity of your code can also reduce your attack surface.</p>

            <p>End your coding sessions with this prompt:</p>

            <blockquote class="rounded-xl border border-neutral-800 bg-neutral-900/80 p-4 font-mono text-sm text-brand-200">
                &ldquo;Read through the changes made in this commit. Flag duplicated processes, dead code, unnecessary abstractions, or useless lines. Do not remove anything until I approve the proposed changes.&rdquo;
            </blockquote>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Reduce Cognitive Debt</h2>

            <p>Cognitive debt is a critical concern in development. I will be exploring this topic in future posts, but let me quickly touch on it here.</p>

            <p>Cognitive debt is the debt accrued when a developer offloads some portion of a project&rsquo;s creation to another actor&mdash;agent or human&mdash;without maintaining an understanding of what was created and why.</p>

            <p>When you think of it simply&mdash;someone else is adding to your project, and you do not fully know what they added&mdash;you can see that cognitive debt is not new, particularly in larger projects.</p>

            <p>But the debt is accumulating quickly as agents produce a larger percentage of the codebase.</p>

            <p class="text-neutral-500 italic dark:text-neutral-400">Vulnerabilities and bugs love debt. It&rsquo;s their natural habitat.</p>

            <p>Make sure you reduce cognitive debt in your projects so that you can spot vulnerabilities and logic bombs before a clanker finds them for you.</p>

            <p>Add this prompt to the end of your sessions:</p>

            <blockquote class="rounded-xl border border-neutral-800 bg-neutral-900/80 p-4 font-mono text-sm text-brand-200">
                &ldquo;Create a short, five-question multiple-choice quiz about the changes in this commit. Ask me the questions one at a time, explain each answer after I respond, and identify anything I may not fully understand.&rdquo;
            </blockquote>

            <h2 class="mt-4 text-xl font-bold text-brand-900 dark:text-brand-400">Clankers Are Coming</h2>

            <p>Clankers are on the rise, and we will see more stories about people like Andrew accidentally breaking applications everywhere.</p>

            <p>Andrew was a nice guy, and this story had a relatively happy ending for everyone except the one person who got booted from the waitlist. The next big story may be much worse.</p>

            <p>The anti-Andrews are out there looking to break your applications.</p>

            <p>By implementing a few small practices, you can begin hardening your applications against the clankers of the future.</p>

            <p>
                Read the full story about Andrew in
                <a href="https://www.abc.net.au/news/2026-08-10/ai-assistant-hacks-gym-website-aus-cyber-attack/107007986" target="_blank" rel="noopener" class="font-bold text-brand-400 underline hover:text-brand-300">ABC News</a>.
            </p>

            <p>
                Read Margaret-Anne Storey&rsquo;s article,
                <a href="https://getdx.com/blog/cognitive-debt-the-hidden-risk-in-ai-driven-software-development/" target="_blank" rel="noopener" class="font-bold text-brand-400 underline hover:text-brand-300">&ldquo;Cognitive Debt: The Hidden Risk in AI-Driven Software Development,&rdquo;</a>
                for a deeper exploration of cognitive debt.
            </p>
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

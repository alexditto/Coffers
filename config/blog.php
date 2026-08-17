<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Posts
    |--------------------------------------------------------------------------
    |
    | To add a new post: create resources/views/blog/posts/{slug}.blade.php,
    | then add an entry here so it shows up in the blog index and sitemap.
    | Keep this ordered newest first.
    |
    */

    'posts' => [
        [
            'slug' => 'epistemological-mapping',
            'title' => 'Let’s Get Dirty with Some Epistemological Mapping',
            'excerpt' => 'A simple four-domain method for mapping what you know—demoed on Eloquent’s often-overlooked isDirty() method.',
            'date' => '2026-08-17',
            'image' => 'epistemological-mapping.png',
        ],
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
    ],

];

<?php

use App\Models\User;

test('the home page is publicly accessible and links to the guide and portfolio', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Create an Account')
        ->assertSee('Log in')
        ->assertSee(route('guide'), false)
        ->assertSee(route('portfolio'), false);
});

test('the guide page is publicly accessible and covers setup, characters, campaigns, shops, and inventory', function () {
    $this->get(route('guide'))
        ->assertOk()
        ->assertSee('DM &amp; player setup', false)
        ->assertSee('Character creation')
        ->assertSee('Campaign creation')
        ->assertSee('Shop control')
        ->assertSee('Inventory management');
});

test('the portfolio page is publicly accessible and links to GitHub, LinkedIn, and a resume', function () {
    $this->get(route('portfolio'))
        ->assertOk()
        ->assertSee('https://github.com/alexditto', false)
        ->assertSee('https://www.linkedin.com/in/alexander-ditto-0a69aa141/', false)
        ->assertSee('https://github.com/alexditto/Resume', false);
});

test('the portfolio page describes what kind of work is wanted', function () {
    $this->get(route('portfolio'))
        ->assertOk()
        ->assertSee('Remote or onsite', false)
        ->assertSee('Laravel applications')
        ->assertSee('NetSuite applications')
        ->assertSee('Automations', false);
});

test('the portfolio page shows three benchmark project overviews', function () {
    $this->get(route('portfolio'))
        ->assertOk()
        ->assertSee('Project One')
        ->assertSee('Project Two')
        ->assertSee('Project Three')
        ->assertSeeInOrder([
            asset('img/benchmark-1.png'),
            asset('img/benchmark-1.png'),
            asset('img/benchmark-1.png'),
        ], false);
});

test('authenticated users can still visit the public marketing pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk();
    $this->actingAs($user)->get(route('guide'))->assertOk();
    $this->actingAs($user)->get(route('portfolio'))->assertOk();
});

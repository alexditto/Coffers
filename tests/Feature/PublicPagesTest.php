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

test('the portfolio page is publicly accessible and shows a coming soon placeholder', function () {
    $this->get(route('portfolio'))
        ->assertOk()
        ->assertSee('Portfolio coming soon');
});

test('authenticated users can still visit the public marketing pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk();
    $this->actingAs($user)->get(route('guide'))->assertOk();
    $this->actingAs($user)->get(route('portfolio'))->assertOk();
});

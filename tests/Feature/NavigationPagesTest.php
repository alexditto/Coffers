<?php

use App\Models\User;

test('guests are redirected to the login page', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'character-page',
    'characters-page',
    'shops',
    'inventory',
    'scenes',
    'journal',
]);

test('authenticated users can visit each navigation page', function (string $routeName) {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route($routeName))->assertOk();
})->with([
    'character-page',
    'characters-page',
    'shops',
    'inventory',
    'scenes',
    'journal',
]);

<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard explains the app and links to friends and campaign builder', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Gather your party')
        ->assertSee('Create a campaign or build a character')
        ->assertSee('Open the shops')
        ->assertSee('Fill the coffers')
        ->assertSee(route('friends'), false)
        ->assertSee(route('campaign-builder'), false);
});

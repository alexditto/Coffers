<?php

use App\Models\Friend;
use App\Models\User;
use Livewire\Livewire;

test('shows zero counts when the user has no friends', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('dashboard-friends')
        ->assertSee('0');

    expect($component->get('approvedFriends'))->toBeEmpty()
        ->and($component->get('pendingFriends'))->toBeEmpty()
        ->and($component->get('pendingRequests'))->toBeEmpty();
});

test('counts approved friends, friends awaiting approval, and incoming requests separately', function () {
    $user = User::factory()->create();
    $otherUsers = User::factory()->count(4)->create();

    // Friendships the user initiated.
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $otherUsers[0]->id, 'status' => 'approved']);
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $otherUsers[1]->id, 'status' => 'pending']);

    // Requests sent to the user by others, awaiting the user's response.
    Friend::factory()->create(['user_id' => $otherUsers[2]->id, 'friend_id' => $user->id, 'status' => 'pending']);
    Friend::factory()->create(['user_id' => $otherUsers[3]->id, 'friend_id' => $user->id, 'status' => 'pending']);

    $component = Livewire::actingAs($user)->test('dashboard-friends');

    expect($component->get('approvedFriends'))->toHaveCount(1)
        ->and($component->get('pendingFriends'))->toHaveCount(1)
        ->and($component->get('pendingRequests'))->toHaveCount(2);
});

test('links to the friends page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee(route('friends'), false);
});

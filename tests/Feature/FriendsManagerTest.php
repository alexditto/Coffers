<?php

use App\Models\Friend;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('friends'))->assertRedirect(route('login'));
});

test('authenticated users can visit the friends page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('friends'))->assertOk();
});

test('friends list includes approved friendships in either direction', function () {
    $user = User::factory()->create();
    $initiatedFriend = User::factory()->create();
    $receivedFriend = User::factory()->create();

    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $initiatedFriend->id, 'status' => 'approved']);
    Friend::factory()->create(['user_id' => $receivedFriend->id, 'friend_id' => $user->id, 'status' => 'approved']);

    $component = Livewire::actingAs($user)->test('friends-manager');

    expect($component->get('friends')->pluck('id')->all())
        ->toEqualCanonicalizing([$initiatedFriend->id, $receivedFriend->id]);
});

test('can send a friend request by email', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->set('friendEmail', $target->email)
        ->call('sendRequest')
        ->assertHasNoErrors();

    expect(Friend::query()
        ->where('user_id', $user->id)
        ->where('friend_id', $target->id)
        ->where('status', 'pending')
        ->exists())->toBeTrue();
});

test('cannot send a friend request to yourself', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->set('friendEmail', $user->email)
        ->call('sendRequest')
        ->assertHasErrors(['friendEmail']);

    expect(Friend::count())->toBe(0);
});

test('cannot send a friend request to an unknown email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->set('friendEmail', 'nobody@example.com')
        ->call('sendRequest')
        ->assertHasErrors(['friendEmail']);

    expect(Friend::count())->toBe(0);
});

test('cannot send a duplicate friend request', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $target->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->set('friendEmail', $target->email)
        ->call('sendRequest')
        ->assertHasErrors(['friendEmail']);

    expect(Friend::count())->toBe(1);
});

test('can approve an incoming friend request', function () {
    $user = User::factory()->create();
    $requester = User::factory()->create();

    $friendRequest = Friend::factory()->create(['user_id' => $requester->id, 'friend_id' => $user->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->call('approve', $friendRequest->id);

    expect($friendRequest->fresh()->status)->toBe('approved');
});

test('can reject an incoming friend request', function () {
    $user = User::factory()->create();
    $requester = User::factory()->create();

    $friendRequest = Friend::factory()->create(['user_id' => $requester->id, 'friend_id' => $user->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->call('reject', $friendRequest->id);

    expect(Friend::find($friendRequest->id))->toBeNull();
});

test('cannot approve or reject a friend request that does not belong to them', function () {
    $user = User::factory()->create();
    $requester = User::factory()->create();
    $recipient = User::factory()->create();

    $friendRequest = Friend::factory()->create(['user_id' => $requester->id, 'friend_id' => $recipient->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test('friends-manager')
        ->call('approve', $friendRequest->id)
        ->call('reject', $friendRequest->id);

    expect($friendRequest->fresh()->status)->toBe('pending');
});

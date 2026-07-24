<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Friend;
use App\Models\User;
use Livewire\Livewire;

test('shows the characters already in the campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $player = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $player->id, 'campaign_id' => $campaign->id, 'name' => 'Thornwick']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('campaign-characters')
        ->assertSee('Thornwick')
        ->assertSee($player->name);
});

test('non owners cannot see the add character form or remove button', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    Character::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Thornwick']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('campaign-characters')
        ->assertSee('Thornwick')
        ->assertDontSee('Add a character')
        ->assertDontSee('Remove');
});

test('the owner can only add unassigned characters belonging to approved friends', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $owner->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $friendCharacter = Character::factory()->create(['user_id' => $friend->id, 'campaign_id' => null, 'name' => 'Available Friend Character']);

    $assignedFriendCharacter = Character::factory()->create(['user_id' => $friend->id, 'campaign_id' => Campaign::factory(), 'name' => 'Already Assigned']);

    $stranger = User::factory()->create();
    $strangerCharacter = Character::factory()->create(['user_id' => $stranger->id, 'campaign_id' => null, 'name' => 'Stranger Character']);

    $pendingFriend = User::factory()->create();
    Friend::factory()->create(['user_id' => $owner->id, 'friend_id' => $pendingFriend->id, 'status' => 'pending']);
    $pendingFriendCharacter = Character::factory()->create(['user_id' => $pendingFriend->id, 'campaign_id' => null, 'name' => 'Pending Friend Character']);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($owner)->test('campaign-characters');

    expect($component->get('availableFriendCharacters')->pluck('id')->all())
        ->toBe([$friendCharacter->id]);

    $component->assertSee('Available Friend Character')
        ->assertDontSee('Already Assigned')
        ->assertDontSee('Stranger Character')
        ->assertDontSee('Pending Friend Character');
});

test('the owner can add an available friend character to the campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $owner->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $friendCharacter = Character::factory()->create(['user_id' => $friend->id, 'campaign_id' => null]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('campaign-characters')
        ->set('selectedCharacterId', $friendCharacter->id)
        ->call('addCharacter')
        ->assertHasNoErrors();

    expect($friendCharacter->fresh()->campaign_id)->toBe($campaign->id);
});

test('cannot add a character that is not an available friend character', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $stranger = User::factory()->create();
    $strangerCharacter = Character::factory()->create(['user_id' => $stranger->id, 'campaign_id' => null]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('campaign-characters')
        ->set('selectedCharacterId', $strangerCharacter->id)
        ->call('addCharacter')
        ->assertHasErrors(['selectedCharacterId']);

    expect($strangerCharacter->fresh()->campaign_id)->toBeNull();
});

test('a non owner cannot add or remove characters', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $member->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $friendCharacter = Character::factory()->create(['user_id' => $friend->id, 'campaign_id' => null]);
    $inCampaignCharacter = Character::factory()->create(['campaign_id' => $campaign->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('campaign-characters')
        ->set('selectedCharacterId', $friendCharacter->id)
        ->call('addCharacter')
        ->assertForbidden();

    Livewire::actingAs($member)
        ->test('campaign-characters')
        ->call('removeCharacter', $inCampaignCharacter->id)
        ->assertForbidden();

    expect($friendCharacter->fresh()->campaign_id)->toBeNull()
        ->and($inCampaignCharacter->fresh()->campaign_id)->toBe($campaign->id);
});

test('the owner can remove a character from the campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['campaign_id' => $campaign->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('campaign-characters')
        ->call('removeCharacter', $character->id);

    expect($character->fresh()->campaign_id)->toBeNull();
});

test('cannot remove a character belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherCampaignCharacter = Character::factory()->create();
    $originalCampaignId = $otherCampaignCharacter->campaign_id;

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('campaign-characters')
        ->call('removeCharacter', $otherCampaignCharacter->id);

    expect($otherCampaignCharacter->fresh()->campaign_id)->toBe($originalCampaignId);
});

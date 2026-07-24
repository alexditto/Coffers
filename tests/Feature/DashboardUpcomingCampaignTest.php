<?php

use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when the user has no upcoming sessions', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('dashboard-upcoming-campaign')
        ->assertSee('No sessions on the calendar');
});

test('ignores campaigns with no scheduled session or a session in the past', function () {
    $user = User::factory()->create();

    $noDate = Campaign::factory()->create(['name' => 'Undated Campaign', 'next_session_date' => null]);
    $past = Campaign::factory()->create(['name' => 'Past Campaign', 'next_session_date' => now()->subDay()]);
    $user->campaigns()->attach([$noDate->id, $past->id]);

    Livewire::actingAs($user)
        ->test('dashboard-upcoming-campaign')
        ->assertSee('No sessions on the calendar')
        ->assertDontSee('Undated Campaign')
        ->assertDontSee('Past Campaign');
});

test('selects the soonest upcoming campaign from campaigns the user belongs to', function () {
    $user = User::factory()->create();

    $sooner = Campaign::factory()->create(['name' => 'Port Namas', 'next_session_date' => now()->addDays(2)]);
    $later = Campaign::factory()->create(['name' => 'Crimson Vale', 'next_session_date' => now()->addDays(10)]);
    $user->campaigns()->attach([$sooner->id, $later->id]);

    $component = Livewire::actingAs($user)->test('dashboard-upcoming-campaign');

    expect($component->get('upcomingCampaign')->id)->toBe($sooner->id);

    $component->assertSee('Port Namas')->assertDontSee('Crimson Vale');
});

test('includes campaigns owned by the user, not just campaigns they belong to', function () {
    $user = User::factory()->create();

    $owned = Campaign::factory()->create([
        'name' => 'Waterdeep Nights',
        'owner_id' => $user->id,
        'next_session_date' => now()->addDay(),
    ]);

    $component = Livewire::actingAs($user)->test('dashboard-upcoming-campaign');

    expect($component->get('upcomingCampaign')->id)->toBe($owned->id);
});

test('treats a session scheduled for today as upcoming', function () {
    $user = User::factory()->create();

    $today = Campaign::factory()->create(['name' => 'Tonight at the Table', 'next_session_date' => now()]);
    $user->campaigns()->attach($today->id);

    Livewire::actingAs($user)
        ->test('dashboard-upcoming-campaign')
        ->assertSee('Tonight at the Table')
        ->assertSee('Today');
});

test('links to the campaign builder page for the upcoming campaign', function () {
    $user = User::factory()->create();

    $campaign = Campaign::factory()->create(['next_session_date' => now()->addDay()]);
    $user->campaigns()->attach($campaign->id);

    Livewire::actingAs($user)
        ->test('dashboard-upcoming-campaign')
        ->assertSeeHtml(route('campaign-builder', ['campaign' => $campaign->id]));
});

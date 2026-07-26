<?php

use App\Filament\Widgets\NewUsersChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\Shop;
use App\Models\User;
use Livewire\Livewire;

test('the admin dashboard shows the stats overview and new users chart widgets', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeLivewire(StatsOverview::class)
        ->assertSeeLivewire(NewUsersChart::class);
});

test('the stats overview widget counts users, campaigns, characters, and shops', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['role' => 'admin']);
    User::factory()->count(3)->create(['role' => 'user']);
    Campaign::factory()->count(2)->create();
    Character::factory()->count(4)->create();
    Shop::factory()->count(5)->create();

    Livewire::actingAs($admin)
        ->test(StatsOverview::class)
        ->assertSee('Users')
        ->assertSee('4') // 3 regular users + admin excluded
        ->assertSee('Campaigns')
        ->assertSee('2')
        ->assertSee('Characters')
        ->assertSee('Shops')
        ->assertSee('5');
});

test('the stats overview widget excludes admins from the users count', function () {
    User::factory()->count(2)->create(['role' => 'admin']);
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(StatsOverview::class)
        ->assertSee('0');
});

test('the new users chart groups non admin users by month and excludes admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    User::factory()->create(['role' => 'user', 'created_at' => now()]);
    User::factory()->create(['role' => 'user', 'created_at' => now()]);
    User::factory()->create(['role' => 'user', 'created_at' => now()->subMonth()]);
    User::factory()->create(['role' => 'admin', 'created_at' => now()]);

    $widget = new NewUsersChart;

    $data = invade($widget)->getData();

    expect($data['labels'])->toHaveCount(12)
        ->and($data['labels'][11])->toBe(now()->format('M Y'))
        ->and($data['datasets'][0]['data'][11])->toBe(2)
        ->and($data['datasets'][0]['data'][10])->toBe(1);
});

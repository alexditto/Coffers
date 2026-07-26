<?php

use App\Filament\Resources\Characters\Pages\CreateCharacter;
use App\Filament\Resources\Characters\Pages\EditCharacter;
use App\Filament\Resources\Characters\Pages\ViewCharacter;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\User;
use Livewire\Livewire;

test('creating a character also creates its character sheet', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateCharacter::class)
        ->fillForm([
            'user_id' => $user->id,
            'name' => 'Dockmaster Hale',
            'character_sheet' => [
                'class' => 'fighter',
                'race' => 'human',
                'level' => 3,
                'ac' => 15,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $character = Character::where('name', 'Dockmaster Hale')->firstOrFail();

    expect($character->character_sheet)->not->toBeNull()
        ->and($character->character_sheet->class)->toBe('fighter')
        ->and($character->character_sheet->level)->toBe(3)
        ->and($character->character_sheet->ac)->toBe(15);
});

test('editing a character updates its existing character sheet', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $character = Character::factory()->create();
    CharacterSheet::factory()->create(['character_id' => $character->id, 'class' => 'rogue']);

    Livewire::actingAs($admin)
        ->test(EditCharacter::class, ['record' => $character->id])
        ->fillForm([
            'character_sheet' => [
                'class' => 'wizard',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($character->character_sheet()->count())->toBe(1)
        ->and($character->character_sheet->fresh()->class)->toBe('wizard');
});

test('the character sheet fields are shown on the view page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $character = Character::factory()->create();
    CharacterSheet::factory()->create(['character_id' => $character->id, 'class' => 'ranger']);

    Livewire::actingAs($admin)
        ->test(ViewCharacter::class, ['record' => $character->id])
        ->assertOk()
        ->assertSee('ranger');
});

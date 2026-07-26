<?php

use App\Models\Character;
use App\Models\CharacterSheet;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    const ALIGNMENTS = [
        'lawful good' => 'Lawful Good',
        'neutral good' => 'Neutral Good',
        'chaotic good' => 'Chaotic Good',
        'lawful neutral' => 'Lawful Neutral',
        'true neutral' => 'True Neutral',
        'chaotic neutral' => 'Chaotic Neutral',
        'lawful evil' => 'Lawful Evil',
        'neutral evil' => 'Neutral Evil',
        'chaotic evil' => 'Chaotic Evil',
    ];

    public ?int $editingCharacterId = null;

    public string $characterName = '';

    public string $characterImage = '';

    public string $characterClass = '';

    public string $characterRace = '';

    public int $characterLevel = 1;

    public string $characterAlignment = 'true neutral';

    public string $characterBackground = '';

    public string $characterDescription = '';

    public int $characterTotalHealth = 10;

    public int $characterAc = 10;

    public ?int $deletingCharacterId = null;

    /**
     * @return Collection<int, Character>
     */
    #[Computed]
    public function characters(): Collection
    {
        return auth()->user()->characters()
            ->with(['character_sheet', 'campaign'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function deletingCharacter(): ?Character
    {
        if (! $this->deletingCharacterId) {
            return null;
        }

        return auth()->user()->characters()->where('id', $this->deletingCharacterId)->with('campaign')->first();
    }

    /**
     * @return array<string, string>
     */
    public function alignmentOptions(): array
    {
        return self::ALIGNMENTS;
    }

    public function newCharacter(): void
    {
        $this->editingCharacterId = null;
        $this->characterName = '';
        $this->characterImage = '';
        $this->characterClass = '';
        $this->characterRace = '';
        $this->characterLevel = 1;
        $this->characterAlignment = 'true neutral';
        $this->characterBackground = '';
        $this->characterDescription = '';
        $this->characterTotalHealth = 10;
        $this->characterAc = 10;

        Flux::modal('edit-character')->show();
    }

    public function edit(int $characterId): void
    {
        $character = auth()->user()->characters()->where('id', $characterId)->with('character_sheet')->first();

        if (! $character) {
            return;
        }

        $sheet = $character->character_sheet;

        $this->editingCharacterId = $character->id;
        $this->characterName = $character->name;
        $this->characterImage = $character->image ?? '';
        $this->characterClass = $sheet?->class ?? '';
        $this->characterRace = $sheet?->race ?? '';
        $this->characterLevel = $sheet?->level ?? 1;
        $this->characterAlignment = $sheet?->alignment ?? 'true neutral';
        $this->characterBackground = $sheet?->background ?? '';
        $this->characterDescription = $sheet?->description ?? '';
        $this->characterTotalHealth = $sheet?->total_health ?? 10;
        $this->characterAc = $sheet?->ac ?? 10;

        Flux::modal('edit-character')->show();
    }

    public function save(): void
    {
        $data = $this->validate([
            'characterName' => ['required', 'string', 'max:255'],
            'characterImage' => ['nullable', 'string', 'max:2048'],
            'characterClass' => ['nullable', 'string', 'max:255'],
            'characterRace' => ['nullable', 'string', 'max:255'],
            'characterLevel' => ['required', 'integer', 'min:1', 'max:20'],
            'characterAlignment' => ['required', Rule::in(array_keys(self::ALIGNMENTS))],
            'characterBackground' => ['nullable', 'string', 'max:1000'],
            'characterDescription' => ['nullable', 'string', 'max:1000'],
            'characterTotalHealth' => ['required', 'integer', 'min:1'],
            'characterAc' => ['required', 'integer', 'min:0'],
        ]);

        if ($this->editingCharacterId) {
            $character = auth()->user()->characters()->where('id', $this->editingCharacterId)->with('character_sheet')->first();

            if (! $character) {
                return;
            }

            $character->update([
                'name' => $data['characterName'],
                'image' => $data['characterImage'] ?: null,
            ]);

            $this->saveSheet($character, $data);

            Flux::toast('Character updated.', variant: 'success');
        } else {
            $character = auth()->user()->characters()->create([
                'name' => $data['characterName'],
                'image' => $data['characterImage'] ?: null,
            ]);

            $this->saveSheet($character, $data);

            Flux::toast('Character created.', variant: 'success');
        }

        unset($this->characters);

        Flux::modal('edit-character')->close();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveSheet(Character $character, array $data): void
    {
        $sheet = $character->character_sheet;

        $attributes = [
            'class' => $data['characterClass'] ?: null,
            'race' => $data['characterRace'] ?: null,
            'level' => $data['characterLevel'],
            'alignment' => $data['characterAlignment'],
            'background' => $data['characterBackground'] ?: null,
            'description' => $data['characterDescription'] ?: null,
            'total_health' => $data['characterTotalHealth'],
            'ac' => $data['characterAc'],
        ];

        if ($sheet) {
            // Never let a lowered max health silently push current health above it.
            $attributes['health'] = min($sheet->health, $data['characterTotalHealth']);

            $sheet->update($attributes);

            return;
        }

        $attributes['character_id'] = $character->id;
        $attributes['health'] = $data['characterTotalHealth'];

        $sheet = CharacterSheet::create($attributes);

        $character->update(['character_sheet_id' => $sheet->id]);
    }

    public function duplicate(int $characterId): void
    {
        $character = auth()->user()->characters()->where('id', $characterId)->with('character_sheet')->first();

        if (! $character) {
            return;
        }

        // Duplicates always start fresh: unattached to any campaign and with no
        // inventory of their own - a DM can attach the copy to a campaign later.
        $copy = auth()->user()->characters()->create([
            'name' => $character->name.' (Copy)',
            'image' => $character->image,
        ]);

        if ($character->character_sheet) {
            $source = $character->character_sheet;

            $sheet = CharacterSheet::create([
                'character_id' => $copy->id,
                'description' => $source->description,
                'class' => $source->class,
                'race' => $source->race,
                'level' => $source->level,
                'alignment' => $source->alignment,
                'background' => $source->background,
                'total_health' => $source->total_health,
                'health' => $source->total_health,
                'ac' => $source->ac,
            ]);

            $copy->update(['character_sheet_id' => $sheet->id]);
        }

        unset($this->characters);

        Flux::toast($character->name.' duplicated.', variant: 'success');
    }

    public function confirmDelete(): void
    {
        if (! $this->editingCharacterId) {
            return;
        }

        $this->deletingCharacterId = $this->editingCharacterId;

        Flux::modal('edit-character')->close();
        Flux::modal('confirm-delete')->show();
    }

    public function deleteCharacter(): void
    {
        $character = auth()->user()->characters()->where('id', $this->deletingCharacterId)
            ->with(['character_sheet', 'inventory'])
            ->first();

        if (! $character) {
            return;
        }

        $name = $character->name;

        DB::transaction(function () use ($character) {
            $character->character_sheet?->delete();
            $character->inventory?->delete();
            $character->delete();
        });

        $this->deletingCharacterId = null;
        $this->editingCharacterId = null;

        unset($this->characters);

        Flux::modal('confirm-delete')->close();

        Flux::toast($name.' deleted.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Characters</flux:heading>
        <flux:button variant="primary" size="sm" wire:click="newCharacter">+ New character</flux:button>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        @forelse ($this->characters as $character)
            @php $sheet = $character->character_sheet; @endphp

            <div wire:key="character-{{ $character->id }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
                <div wire:click="edit({{ $character->id }})" class="cursor-pointer p-3 transition hover:bg-canvas">
                    <div class="flex items-center gap-3">
                        @if ($character->image)
                            <img src="{{ $character->image }}" alt="{{ $character->name }}" class="size-12 shrink-0 rounded-xl border border-line object-cover" />
                        @else
                            <flux:avatar size="lg" name="{{ $character->name }}" color="auto" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $character->name }}</div>
                            <div class="truncate text-xs text-content-muted">
                                {{ $sheet?->race ? ucfirst($sheet->race) : 'Unknown race' }}
                                · {{ $sheet?->class ? ucfirst($sheet->class) : 'Unknown class' }}
                                · Lvl {{ $sheet?->level ?? 1 }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if ($character->campaign)
                            <flux:badge size="sm" color="green">In {{ $character->campaign->name }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Unattached</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="border-t border-line p-2">
                    <flux:button size="sm" variant="ghost" class="w-full" wire:click="duplicate({{ $character->id }})">
                        Duplicate
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                You haven't created any characters yet.
            </div>
        @endforelse
    </div>

    <flux:modal name="edit-character" class="md:w-[28rem]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingCharacterId ? 'Edit Character' : 'New Character' }}</flux:heading>
                <flux:text class="mt-2">
                    {{ $editingCharacterId ? 'Update this character.' : "This character won't be in a campaign until a DM attaches them to one." }}
                </flux:text>
            </div>

            <flux:input wire:model="characterName" label="Name" placeholder="e.g. Thornwick" />

            <flux:input wire:model="characterImage" label="Image URL" placeholder="https://..." />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="characterClass" label="Class" placeholder="e.g. Rogue" />
                <flux:input wire:model="characterRace" label="Race" placeholder="e.g. Elf" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="characterLevel" type="number" min="1" max="20" label="Level" />

                <flux:select wire:model="characterAlignment" label="Alignment">
                    @foreach ($this->alignmentOptions() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="characterBackground" label="Background" rows="2" />

            <flux:textarea wire:model="characterDescription" label="Description" rows="2" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="characterTotalHealth" type="number" min="1" label="Max Health" />
                <flux:input wire:model="characterAc" type="number" min="0" label="AC" />
            </div>

            <div class="flex items-center gap-2">
                @if ($editingCharacterId)
                    <flux:button variant="danger" wire:click="confirmDelete">Delete</flux:button>
                @endif

                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">
                    {{ $editingCharacterId ? 'Save changes' : 'Create character' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete character?</flux:heading>
                <flux:text class="mt-2">
                    This will permanently delete {{ $this->deletingCharacter?->name }}.
                    @if ($this->deletingCharacter?->campaign)
                        They are currently attached to {{ $this->deletingCharacter->campaign->name }}.
                    @endif
                    This can't be undone.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteCharacter">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

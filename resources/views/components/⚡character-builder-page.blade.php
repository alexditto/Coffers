<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

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

    public $characterImage = null;

    public ?string $currentCharacterImageUrl = null;

    public string $characterClass = '';

    public string $characterRace = '';

    public int $characterLevel = 1;

    public string $characterAlignment = 'true neutral';

    public string $characterBackground = '';

    public string $characterDescription = '';

    public int $characterTotalHealth = 10;

    public int $characterAc = 10;

    public ?int $deletingCharacterId = null;

    public ?int $joiningCharacterId = null;

    public string $joinCampaignId = '';

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
    public function joiningCharacter(): ?Character
    {
        if (! $this->joiningCharacterId) {
            return null;
        }

        return auth()->user()->characters()->whereNull('campaign_id')->where('id', $this->joiningCharacterId)->first();
    }

    /**
     * The ids of users the current player has an approved friendship with, in either direction.
     *
     * @return Collection<int, int>
     */
    #[Computed]
    public function friendIds(): Collection
    {
        $user = auth()->user();

        $initiated = $user->friends()->where('status', 'approved')->pluck('friend_id');
        $received = $user->friend_requests()->where('status', 'approved')->pluck('user_id');

        return $initiated->merge($received)->unique()->values();
    }

    /**
     * Campaigns owned by an approved friend, available to join with the character being attached.
     *
     * @return Collection<int, Campaign>
     */
    #[Computed]
    public function friendCampaigns(): Collection
    {
        return Campaign::query()
            ->whereIn('owner_id', $this->friendIds->all())
            ->with('owner')
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
        $this->characterImage = null;
        $this->currentCharacterImageUrl = null;
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
        $this->characterImage = null;
        $this->currentCharacterImageUrl = $character->image;
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
            'characterImage' => ['nullable', 'image', 'max:5120'],
            'characterClass' => ['nullable', 'string', 'max:255'],
            'characterRace' => ['nullable', 'string', 'max:255'],
            'characterLevel' => ['required', 'integer', 'min:1', 'max:20'],
            'characterAlignment' => ['required', Rule::in(array_keys(self::ALIGNMENTS))],
            'characterBackground' => ['nullable', 'string', 'max:1000'],
            'characterDescription' => ['nullable', 'string', 'max:1000'],
            'characterTotalHealth' => ['required', 'integer', 'min:1'],
            'characterAc' => ['required', 'integer', 'min:0'],
        ]);

        $imageUrl = $this->characterImage
            ? Storage::disk('s3')->url(Image::fromUpload($this->characterImage)->scale(800, 800)->store('characters', 's3'))
            : null;

        if ($this->editingCharacterId) {
            $character = auth()->user()->characters()->where('id', $this->editingCharacterId)->with('character_sheet')->first();

            if (! $character) {
                return;
            }

            $character->update([
                'name' => $data['characterName'],
                'image' => $imageUrl ?? $character->image,
            ]);

            $this->saveSheet($character, $data);

            Flux::toast('Character updated.', variant: 'success');
        } else {
            $character = auth()->user()->characters()->create([
                'name' => $data['characterName'],
                'image' => $imageUrl,
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

    public function joinCampaign(int $characterId): void
    {
        $this->joiningCharacterId = $characterId;
        $this->joinCampaignId = '';

        unset($this->joiningCharacter, $this->friendIds, $this->friendCampaigns);

        Flux::modal('join-campaign')->show();
    }

    public function confirmJoinCampaign(): void
    {
        $data = $this->validate([
            'joinCampaignId' => ['required', 'integer'],
        ]);

        $character = $this->joiningCharacter;

        if (! $character) {
            return;
        }

        $campaign = $this->friendCampaigns->firstWhere('id', (int) $data['joinCampaignId']);

        if (! $campaign) {
            $this->addError('joinCampaignId', 'That campaign is no longer available to join.');

            return;
        }

        $character->update(['campaign_id' => $campaign->id]);

        auth()->user()->campaigns()->syncWithoutDetaching([$campaign->id]);

        $this->joiningCharacterId = null;

        unset($this->characters, $this->joiningCharacter, $this->friendCampaigns);

        Flux::modal('join-campaign')->close();

        Flux::toast($character->name.' joined '.$campaign->name.'.', variant: 'success');
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

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Characters</flux:heading>
        <flux:button variant="primary" size="sm" wire:click="newCharacter">+ New character</flux:button>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        @forelse ($this->characters as $character)
            @php $sheet = $character->character_sheet; @endphp

            <div wire:key="character-{{ $character->id }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm dark:border-gray-700 dark:bg-gray-700">
                <div wire:click="edit({{ $character->id }})" class="cursor-pointer p-3 transition hover:bg-canvas">
                    <div class="flex items-center gap-3">
                        @if ($character->image)
                            <img src="{{ $character->image }}" alt="{{ $character->name }}" class="size-12 shrink-0 rounded-xl border border-line object-cover" />
                        @else
                            <flux:avatar size="lg" name="{{ $character->name }}" color="auto" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content dark:text-white">{{ $character->name }}</div>
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

                <div class="flex border-t border-line p-2">
                    <flux:button size="sm" variant="ghost" class="flex-1" wire:click="duplicate({{ $character->id }})">
                        Duplicate
                    </flux:button>

                    @if (! $character->campaign)
                        <flux:button size="sm" variant="ghost" class="flex-1" wire:click="joinCampaign({{ $character->id }})">
                            Join campaign
                        </flux:button>
                    @endif
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

            <flux:field>
                <flux:label>Image</flux:label>

                <div class="flex items-center gap-3">
                    @if ($characterImage)
                        <img src="{{ $characterImage->temporaryUrl() }}" alt="Preview" class="size-14 shrink-0 rounded-xl border border-line object-cover" />
                    @elseif ($currentCharacterImageUrl)
                        <img src="{{ $currentCharacterImageUrl }}" alt="Current image" class="size-14 shrink-0 rounded-xl border border-line object-cover" />
                    @else
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-line text-content-faint">
                            <flux:icon.photo class="size-5" />
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <input
                            type="file"
                            wire:model="characterImage"
                            accept="image/*"
                            class="block w-full text-sm text-content-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-700"
                        />
                        <div wire:loading wire:target="characterImage" class="mt-1 text-xs text-content-muted">Uploading…</div>
                    </div>
                </div>

                <flux:error name="characterImage" />
            </flux:field>

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

    <flux:modal name="join-campaign" class="md:w-96">
        <form wire:submit="confirmJoinCampaign" class="space-y-6">
            <div>
                <flux:heading size="lg">Join a campaign</flux:heading>
                <flux:text class="mt-2">
                    Attach {{ $this->joiningCharacter?->name }} to a campaign run by one of your friends.
                </flux:text>
            </div>

            @if ($this->friendCampaigns->isEmpty())
                <p class="text-sm text-content-muted">None of your friends have a campaign to join right now.</p>
            @else
                <div>
                    <flux:select wire:model="joinCampaignId" placeholder="Choose a campaign…">
                        @foreach ($this->friendCampaigns as $campaign)
                            <flux:select.option value="{{ $campaign->id }}">{{ $campaign->name }} ({{ $campaign->owner->name }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="joinCampaignId" />
                </div>
            @endif

            <div class="flex items-center gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                @if ($this->friendCampaigns->isNotEmpty())
                    <flux:button type="submit" variant="primary">Join</flux:button>
                @endif
            </div>
        </form>
    </flux:modal>
</div>

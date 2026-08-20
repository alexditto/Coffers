<?php

use App\Models\Campaign;
use App\Models\Character;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $campaignId = null;

    public string $selectedCharacterId = '';

    public function mount(): void
    {
        $this->campaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->campaignId = $campaignId;
        $this->selectedCharacterId = '';

        if(session('selected_campaign_role') !== 'dm'){
            redirect('/dashboard');
        }

        unset($this->campaign, $this->characters, $this->availableFriendCharacters);
    }

    /**
     * Only campaigns the user belongs to or owns are considered accessible.
     */
    #[Computed]
    public function campaign(): ?Campaign
    {
        if (! $this->campaignId) {
            return null;
        }

        $user = auth()->user();

        return $user->campaigns->merge($user->owned_campaigns)
            ->unique('id')
            ->firstWhere('id', $this->campaignId);
    }

    #[Computed]
    public function isOwner(): bool
    {
        return (bool) $this->campaign && $this->campaign->owner_id === auth()->id();
    }

    /**
     * @return Collection<int, Character>
     */
    #[Computed]
    public function characters(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        return $this->campaign->characters()->with('user')->orderBy('name')->get();
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
     * Unassigned characters belonging to friends, available to add to this campaign.
     *
     * @return Collection<int, Character>
     */
    #[Computed]
    public function availableFriendCharacters(): Collection
    {
        if (! $this->isOwner) {
            return collect();
        }

        return Character::query()
            ->whereIn('user_id', $this->friendIds->all())
            ->whereNull('campaign_id')
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    public function addCharacter(): void
    {
        abort_unless($this->isOwner, 403);

        $data = $this->validate([
            'selectedCharacterId' => ['required', 'integer'],
        ]);

        $character = $this->availableFriendCharacters->firstWhere('id', (int) $data['selectedCharacterId']);

        if (! $character) {
            $this->addError('selectedCharacterId', 'That character is no longer available to add.');

            return;
        }

        $character->update(['campaign_id' => $this->campaign->id]);

        $this->reset('selectedCharacterId');

        unset($this->characters, $this->availableFriendCharacters);

        Flux::toast($character->name.' joined the campaign.', variant: 'success');
    }

    public function removeCharacter(int $characterId): void
    {
        abort_unless($this->isOwner, 403);

        $this->campaign->characters()
            ->where('id', $characterId)
            ->get()
            ->each(function (Character $character) {
                $character->update(['campaign_id' => null]);
            });

        unset($this->characters, $this->availableFriendCharacters);
    }
};
?>

<div class="rounded-2xl border border-line bg-surface dark:bg-gray-800 p-5 shadow-sm">
    <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Characters · {{ $this->characters->count() }}</div>

    <div class="mt-3 flex flex-col gap-2">
        @forelse ($this->characters as $character)
            <div wire:key="character-{{ $character->id }}" class="flex items-center gap-3 rounded-xl border border-line p-3">
                <flux:avatar size="sm" name="{{ $character->name }}" color="auto" />

                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-bold text-content">{{ $character->name }}</div>
                    <div class="truncate text-xs text-content-muted">Played by {{ $character->user->name }}</div>
                </div>

                @if ($this->isOwner)
                    <flux:button
                        size="sm"
                        variant="ghost"
                        wire:click="removeCharacter({{ $character->id }})"
                        wire:confirm="Remove {{ $character->name }} from this campaign?"
                    >
                        Remove
                    </flux:button>
                @endif
            </div>
        @empty
            <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                No characters in this campaign yet.
            </div>
        @endforelse
    </div>

    @if ($this->isOwner)
        <flux:separator class="my-5" />

        <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Add a character</div>

        @if ($this->availableFriendCharacters->isEmpty())
            <p class="mt-2 text-sm text-content-muted">None of your friends have created a character to add right now.</p>
            <p class="mt-2 text-sm text-content-muted text-center">Typical PCs, am I right?</p>
        @else
            <form wire:submit="addCharacter" class="mt-3 flex items-start gap-2">
                <div class="flex-1">
                    <flux:select wire:model="selectedCharacterId" placeholder="Choose a character…">
                        @foreach ($this->availableFriendCharacters as $character)
                            <flux:select.option value="{{ $character->id }}">{{ $character->name }} ({{ $character->user->name }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="selectedCharacterId" />
                </div>

                <flux:button type="submit" variant="primary">Add</flux:button>
            </form>
        @endif
    @endif
</div>

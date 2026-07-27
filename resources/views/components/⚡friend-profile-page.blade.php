<?php

use App\Models\Character;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $friendId;

    public function mount(int $friendId): void
    {
        $this->friendId = $friendId;
    }

    /**
     * Only an approved friend of the current user may be viewed - never trust the route id alone.
     */
    #[Computed]
    public function friend(): ?User
    {
        $isApprovedFriend = Friend::query()
            ->where('status', 'approved')
            ->where(fn ($query) => $query->where('user_id', auth()->id())->where('friend_id', $this->friendId))
            ->orWhere(fn ($query) => $query->where('user_id', $this->friendId)->where('friend_id', auth()->id()))
            ->exists();

        if (! $isApprovedFriend) {
            return null;
        }

        return User::find($this->friendId);
    }

    /**
     * @return Collection<int, \App\Models\Campaign>
     */
    #[Computed]
    public function activeCampaigns(): Collection
    {
        if (! $this->friend) {
            return collect();
        }

        return $this->friend->campaigns->merge($this->friend->owned_campaigns)
            ->unique('id')
            ->where('status', 'active')
            ->sortBy('name')
            ->values();
    }

    /**
     * @return Collection<int, Character>
     */
    #[Computed]
    public function characters(): Collection
    {
        if (! $this->friend) {
            return collect();
        }

        return $this->friend->characters()
            ->with(['character_sheet.statuses', 'campaign'])
            ->orderBy('name')
            ->get();
    }
};
?>

<div class="flex flex-col gap-4 pb-4">
    <div>
        <flux:button :href="route('friends')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            Back to Friends
        </flux:button>
    </div>

    @if (! $this->friend)
        <div class="rounded-2xl border-2 border-dashed border-line bg-surface p-6 text-center text-sm text-content-muted dark:bg-gray-800">
            This profile isn't available.
        </div>
    @else
        <div class="flex items-center gap-4 rounded-2xl border border-line bg-surface p-5 shadow-sm dark:bg-gray-800">
            <flux:avatar size="xl" name="{{ $this->friend->name }}" color="auto" />

            <div class="min-w-0 flex-1">
                <flux:heading size="lg">{{ $this->friend->name }}</flux:heading>
                <div class="truncate text-sm text-content-muted">{{ $this->friend->email }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm dark:bg-gray-800">
            <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Active Campaigns · {{ $this->activeCampaigns->count() }}</div>

            <div class="mt-3 flex flex-col gap-2">
                @forelse ($this->activeCampaigns as $campaign)
                    <div wire:key="campaign-{{ $campaign->id }}" class="flex items-center gap-3 rounded-xl border border-line p-3">
                        <flux:avatar size="sm" name="{{ $campaign->name }}" color="auto" />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content dark:text-white">{{ $campaign->name }}</div>
                            <div class="text-xs text-content-muted">
                                {{ $campaign->owner_id === $this->friend->id ? 'Dungeon Master' : 'Player' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                        No active campaigns.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-line bg-surface dark:bg-gray-800 p-5 shadow-sm">
            <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Characters · {{ $this->characters->count() }}</div>

            <div class="mt-3 flex flex-col gap-2">
                @forelse ($this->characters as $character)
                    @php
                        $sheet = $character->character_sheet;
                        $conditions = $sheet?->statuses ?? collect();
                    @endphp

                    <div wire:key="character-{{ $character->id }}" class="rounded-xl border border-line p-3">
                        <div class="flex items-center gap-3">
                            @if ($character->image)
                                <img src="{{ $character->image }}" alt="{{ $character->name }}" class="size-12 shrink-0 rounded-xl border border-line object-cover" />
                            @else
                                <flux:avatar size="lg" name="{{ $character->name }}" color="auto" />
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-bold text-content dark:text-white">{{ $character->name }}</span>

                                    <div class="flex flex-wrap justify-end gap-1">
                                        @foreach ($conditions as $condition)
                                            <flux:badge size="sm" color="amber">{{ strtoupper($condition->name) }}</flux:badge>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-0.5 text-xs text-content-muted dark:text-content-faint">
                                    {{ $sheet?->class ? ucfirst($sheet->class) : 'Unknown class' }} · Lvl {{ $sheet?->level ?? 1 }}
                                    @if ($character->campaign)
                                        · {{ $character->campaign->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                        No characters yet.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>

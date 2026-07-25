<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Inventory;
use App\Models\ItemCount;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $selectedCampaignId = null;

    public string $search = '';

    public ?int $removingItemCountId = null;

    public ?int $givingItemCountId = null;

    public string $giveToCharacterId = '';

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;

        unset($this->campaign, $this->character, $this->itemCounts, $this->partyMembers);
    }

    /**
     * Only campaigns the user belongs to or owns are considered accessible.
     */
    #[Computed]
    public function campaign(): ?Campaign
    {
        if (! $this->selectedCampaignId) {
            return null;
        }

        $user = auth()->user();

        return $user->campaigns->merge($user->owned_campaigns)
            ->unique('id')
            ->firstWhere('id', $this->selectedCampaignId);
    }

    /**
     * The current user's own character in the selected campaign.
     */
    #[Computed]
    public function character(): ?Character
    {
        if (! $this->campaign) {
            return null;
        }

        return auth()->user()->characters()
            ->where('campaign_id', $this->campaign->id)
            ->with('inventory')
            ->first();
    }

    #[Computed]
    public function gold(): int
    {
        return $this->character?->inventory?->gold ?? 0;
    }

    /**
     * @return Collection<int, ItemCount>
     */
    #[Computed]
    public function itemCounts(): Collection
    {
        if (! $this->character?->inventory) {
            return collect();
        }

        return $this->character->inventory->item_counts()
            ->where('count', '>', 0)
            ->when(
                $this->search !== '',
                fn ($query) => $query->whereHas('item', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            )
            ->with('item')
            ->get()
            ->sortBy(fn (ItemCount $itemCount) => $itemCount->item->name)
            ->values();
    }

    /**
     * Every other character in the campaign - possible recipients for "give".
     *
     * @return Collection<int, Character>
     */
    #[Computed]
    public function partyMembers(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        return $this->campaign->characters()
            ->when($this->character, fn ($query) => $query->where('id', '!=', $this->character->id))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function removingItem(): ?ItemCount
    {
        if (! $this->removingItemCountId || ! $this->character?->inventory) {
            return null;
        }

        return $this->character->inventory->item_counts()
            ->where('id', $this->removingItemCountId)
            ->with('item')
            ->first();
    }

    #[Computed]
    public function givingItem(): ?ItemCount
    {
        if (! $this->givingItemCountId || ! $this->character?->inventory) {
            return null;
        }

        return $this->character->inventory->item_counts()
            ->where('id', $this->givingItemCountId)
            ->with('item')
            ->first();
    }

    public function confirmRemove(int $itemCountId): void
    {
        if (! $this->character?->inventory) {
            return;
        }

        $itemCount = $this->character->inventory->item_counts()->where('id', $itemCountId)->first();

        if (! $itemCount) {
            return;
        }

        $this->removingItemCountId = $itemCount->id;

        Flux::modal('confirm-remove')->show();
    }

    public function removeItem(): void
    {
        $itemCount = $this->removingItem;

        if (! $itemCount) {
            return;
        }

        $name = $itemCount->item->name;
        $itemCount->delete();

        $this->removingItemCountId = null;

        unset($this->itemCounts);

        Flux::modal('confirm-remove')->close();

        Flux::toast($name.' removed from your inventory.', variant: 'success');
    }

    public function openGive(int $itemCountId): void
    {
        if (! $this->character?->inventory || $this->partyMembers->isEmpty()) {
            return;
        }

        $itemCount = $this->character->inventory->item_counts()->where('id', $itemCountId)->first();

        if (! $itemCount) {
            return;
        }

        $this->givingItemCountId = $itemCount->id;
        $this->giveToCharacterId = '';

        Flux::modal('give-item')->show();
    }

    public function giveItem(): void
    {
        $itemCount = $this->givingItem;

        if (! $itemCount) {
            return;
        }

        $data = $this->validate([
            'giveToCharacterId' => ['required', 'integer'],
        ]);

        $recipient = $this->partyMembers->firstWhere('id', (int) $data['giveToCharacterId']);

        if (! $recipient) {
            $this->addError('giveToCharacterId', 'Choose a valid party member.');

            return;
        }

        $itemName = $itemCount->item->name;

        DB::transaction(function () use ($itemCount, $recipient) {
            $recipientInventory = $recipient->inventory;

            if (! $recipientInventory) {
                $recipientInventory = Inventory::create(['character_id' => $recipient->id, 'gold' => 0]);
                $recipient->update(['inventory_id' => $recipientInventory->id]);
            }

            $recipientItemCount = ItemCount::firstOrCreate(
                ['inventory_id' => $recipientInventory->id, 'item_id' => $itemCount->item_id],
                ['count' => 0]
            );

            $recipientItemCount->increment('count', $itemCount->count);

            $itemCount->delete();
        });

        $this->givingItemCountId = null;
        $this->reset('giveToCharacterId');

        unset($this->itemCounts, $this->partyMembers);

        Flux::modal('give-item')->close();

        Flux::toast('Gave '.$itemName.' to '.$recipient->name.'.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Inventory</flux:heading>
        <span class="rounded-lg border border-line px-2.5 py-1 text-sm font-bold text-gold-600">{{ $this->gold }} gp</span>
    </div>

    @if (! $this->campaign)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see your inventory.
        </div>
    @elseif (! $this->character)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            You don't have a character in this campaign yet.
        </div>
    @else
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search items..." clearable class="mt-4" />

        <div class="mt-4 flex flex-col gap-2">
            @forelse ($this->itemCounts as $itemCount)
                <div wire:key="item-{{ $itemCount->id }}" class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3">
                    @if ($itemCount->item->image)
                        <img src="{{ $itemCount->item->image }}" alt="{{ $itemCount->item->name }}" class="size-10 shrink-0 rounded-lg border border-line object-cover" />
                    @else
                        <flux:avatar size="sm" name="{{ $itemCount->item->name }}" color="auto" />
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-content">{{ $itemCount->item->name }}</div>

                        @if ($itemCount->item->description)
                            <div class="truncate text-xs text-content-muted">{{ $itemCount->item->description }}</div>
                        @endif
                    </div>

                    <span class="shrink-0 text-sm font-bold text-content-muted">×{{ $itemCount->count }}</span>

                    <div class="flex shrink-0 gap-1">
                        @if ($this->partyMembers->isNotEmpty())
                            <flux:button size="sm" variant="ghost" wire:click="openGive({{ $itemCount->id }})">Give</flux:button>
                        @endif

                        <flux:button size="sm" variant="ghost" wire:click="confirmRemove({{ $itemCount->id }})">Remove</flux:button>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                    @if ($search !== '')
                        No items match "{{ $search }}".
                    @else
                        Your inventory is empty.
                    @endif
                </div>
            @endforelse
        </div>

        <flux:modal name="confirm-remove" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remove item?</flux:heading>
                    <flux:text class="mt-2">
                        This will remove {{ $this->removingItem?->item->name }} from your inventory. This can't be undone.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="removeItem">Remove</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="give-item" class="md:w-96">
            <form wire:submit="giveItem" class="space-y-6">
                <div>
                    <flux:heading size="lg">Give item</flux:heading>
                    <flux:text class="mt-2">
                        Give {{ $this->givingItem?->item->name }} to another member of your party.
                    </flux:text>
                </div>

                <div>
                    <flux:select wire:model="giveToCharacterId" label="Give to" placeholder="Choose a party member…">
                        @foreach ($this->partyMembers as $member)
                            <flux:select.option value="{{ $member->id }}">{{ $member->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="giveToCharacterId" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">Give</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

<?php

use App\Models\Campaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $selectedCampaignId = null;

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;

        unset($this->selectedCampaign);
    }

    /**
     * Only campaigns the user belongs to or owns are considered accessible.
     */
    #[Computed]
    public function selectedCampaign(): ?Campaign
    {
        if (! $this->selectedCampaignId) {
            return null;
        }

        $user = auth()->user();

        return $user->campaigns->merge($user->owned_campaigns)
            ->unique('id')
            ->firstWhere('id', $this->selectedCampaignId);
    }
};
?>

<flux:sidebar.group :heading="$this->selectedCampaign?->name" class="grid">
    <flux:sidebar.item icon="user" :href="route('character-page')" :current="request()->routeIs('character-page')" wire:navigate>
        {{ __('Character') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="building-storefront" :href="route('shops')" :current="request()->routeIs('shops')" wire:navigate>
        {{ __('Shops') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="archive-box" :href="route('inventory')" :current="request()->routeIs('inventory')" wire:navigate>
        {{ __('Inventory') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="book-open" :href="route('journal')" :current="request()->routeIs('journal')" wire:navigate>
        {{ __('Journal') }}
    </flux:sidebar.item>
</flux:sidebar.group>

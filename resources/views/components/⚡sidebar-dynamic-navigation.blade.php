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

    #[Computed]
    public function isDungeonMaster(): bool
    {
        return (bool) $this->selectedCampaign && $this->selectedCampaign->owner_id === auth()->id();
    }
};
?>

<flux:sidebar.nav>
    @if ($this->selectedCampaign)
        @if ($this->isDungeonMaster)
            <livewire:sidebar-d-m-navigation :key="'sidebar-nav-dm-'.$this->selectedCampaign->id" />
        @else
            <livewire:sidebar-player-navigation :key="'sidebar-nav-player-'.$this->selectedCampaign->id" />
        @endif
    @endif
</flux:sidebar.nav>

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
        if(session('selected_campaign_role') !== 'dm'){
            redirect('/dashboard');
        }

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

<div>
    @if (! $this->selectedCampaign)
        <div class="rounded-2xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its shops.
        </div>
    @elseif ($this->isDungeonMaster)
        <livewire:d-m-shop-page :key="'shop-page-dm-'.$this->selectedCampaign->id" />
    @else
        <livewire:player-shop-page :key="'shop-page-player-'.$this->selectedCampaign->id" />
    @endif
</div>

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

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Shops · {{ $this->selectedCampaign?->name }}</div>
    <p class="mt-3 text-sm text-content-muted">Browse the shops available in this campaign. Coming soon.</p>
</div>

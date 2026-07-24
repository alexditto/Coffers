<?php

use App\Models\Campaign;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?int $selectedCampaignId = null;
    public ?Campaign $selectedCampaign = null;
    public string $campaignName = '';

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
        $this->selectedCampaign = $this->campaigns()->firstWhere('id', $this->selectedCampaignId);
        $this->campaignName = $this->selectedCampaign?->name ?? '';
    }

    /**
     * @return Collection<int, Campaign>
     */
    #[Computed]
    public function campaigns(): Collection
    {
        $user = auth()->user();

        return $user->campaigns
            ->merge($user->owned_campaigns)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }
};
?>

<flux:sidebar.nav>
    @if($selectedCampaign)
        <flux:sidebar.group :heading="__($campaignName)" class="grid">
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                               wire:navigate>
                {{ __('Character') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                               wire:navigate>
                {{ __('Shops') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                               wire:navigate>
                {{ __('Inventory') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                               wire:navigate>
                {{ __('Journal') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
    @endif
</flux:sidebar.nav>

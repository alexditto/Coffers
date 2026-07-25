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

<div class="contents">
    @if ($this->selectedCampaign)
        @if ($this->isDungeonMaster)
            <a href="{{ route('characters-page') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs('characters-page') ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.users class="size-5" />
                <span class="text-[10px] font-semibold">Characters</span>
            </a>

            <a href="{{ route('shops') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs(['shops', 'shop-detail']) ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.building-storefront class="size-5" />
                <span class="text-[10px] font-semibold">Shops</span>
            </a>

            <a href="{{ route('scenes') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs('scenes') ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.film class="size-5" />
                <span class="text-[10px] font-semibold">Scenes</span>
            </a>

            <a href="{{ route('journal') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs('journal') ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.book-open class="size-5" />
                <span class="text-[10px] font-semibold">Journal</span>
            </a>
        @else
            <a href="{{ route('character-page') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs('character-page') ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.user class="size-5" />
                <span class="text-[10px] font-semibold">Character</span>
            </a>

            <a href="{{ route('shops') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs(['shops', 'shop-detail']) ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.building-storefront class="size-5" />
                <span class="text-[10px] font-semibold">Shops</span>
            </a>

            <a href="{{ route('journal') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-1 py-2 {{ request()->routeIs('journal') ? 'text-brand-600' : 'text-content-faint' }}">
                <flux:icon.book-open class="size-5" />
                <span class="text-[10px] font-semibold">Journal</span>
            </a>
        @endif
    @endif
</div>

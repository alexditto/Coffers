<?php

use App\Models\Campaign;
use Illuminate\Support\Collection;
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

        unset($this->campaign, $this->shops);
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
     * Shops visible to players: open, closed, and hidden (shown as "unknown").
     * Draft shops are DM scaffolding that's never shown to players at all.
     *
     * @return Collection<int, \App\Models\Shop>
     */
    #[Computed]
    public function shops(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        return $this->campaign->shops()
            ->whereIn('status', ['open', 'closed', 'hidden'])
            ->withCount('stock')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, \App\Models\Shop>
     */
    #[Computed]
    public function openShops(): Collection
    {
        return $this->shops->where('status', 'open')->values();
    }

    /**
     * @return Collection<int, \App\Models\Shop>
     */
    #[Computed]
    public function closedShops(): Collection
    {
        return $this->shops->where('status', 'closed')->values();
    }

    /**
     * Hidden shops - the party knows something is there, but hasn't discovered it yet.
     *
     * @return Collection<int, \App\Models\Shop>
     */
    #[Computed]
    public function unknownShops(): Collection
    {
        return $this->shops->where('status', 'hidden')->values();
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <flux:heading size="lg">Shops</flux:heading>

    @if (! $this->campaign)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its shops.
        </div>
    @elseif ($this->shops->isEmpty())
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            No shops discovered in this campaign yet.
        </div>
    @else
        @if ($this->openShops->isNotEmpty())
            <div class="mt-4 text-[10px] font-bold tracking-widest text-content-faint uppercase">Open · {{ $this->openShops->count() }}</div>

            <div class="mt-2 flex flex-col gap-2">
                @foreach ($this->openShops as $shop)
                    <a
                        wire:key="shop-{{ $shop->id }}"
                        href="{{ route('shop-detail', ['shop' => $shop->id]) }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3 shadow-sm transition hover:border-brand-300"
                    >
                        @if ($shop->image)
                            <img src="{{ $shop->image }}" alt="{{ $shop->name }}" class="size-10 shrink-0 rounded-lg border border-line object-cover" />
                        @else
                            <flux:avatar size="sm" name="{{ $shop->name }}" color="auto" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $shop->name }}</div>
                            <div class="text-xs text-content-muted">{{ $shop->stock_count }} item{{ $shop->stock_count === 1 ? '' : 's' }}</div>
                        </div>

                        <flux:icon.chevron-right class="size-4 shrink-0 text-content-faint" />
                    </a>
                @endforeach
            </div>
        @endif

        @if ($this->closedShops->isNotEmpty())
            <div class="mt-5 text-[10px] font-bold tracking-widest text-content-faint uppercase">Closed · {{ $this->closedShops->count() }}</div>

            <div class="mt-2 flex flex-col gap-2">
                @foreach ($this->closedShops as $shop)
                    <div wire:key="shop-{{ $shop->id }}" class="flex items-center gap-3 rounded-xl border border-line bg-canvas p-3 opacity-70">
                        @if ($shop->image)
                            <img src="{{ $shop->image }}" alt="{{ $shop->name }}" class="size-10 shrink-0 rounded-lg border border-line object-cover" />
                        @else
                            <flux:avatar size="sm" name="{{ $shop->name }}" color="auto" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $shop->name }}</div>
                            <div class="text-xs text-content-muted">{{ $shop->stock_count }} item{{ $shop->stock_count === 1 ? '' : 's' }}</div>
                        </div>

                        <flux:badge size="sm" color="zinc">Closed</flux:badge>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($this->unknownShops->isNotEmpty())
            <div class="mt-5 text-[10px] font-bold tracking-widest text-content-faint uppercase">Unknown · {{ $this->unknownShops->count() }}</div>

            <div class="mt-2 flex flex-col gap-2">
                @foreach ($this->unknownShops as $shop)
                    <div wire:key="shop-{{ $shop->id }}" class="flex items-center gap-3 rounded-xl border-2 border-dashed border-line bg-canvas p-3">
                        <div class="size-10 shrink-0 rounded-lg bg-line"></div>

                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold tracking-widest text-content-faint">??????</div>
                            <div class="text-xs text-content-faint">Not yet discovered</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>

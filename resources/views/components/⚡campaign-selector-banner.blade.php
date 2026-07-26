<?php

use App\Models\Campaign;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?int $selectedCampaignId = null;

    public string $newCampaignName = '';

    public string $newCampaignDescription = '';

    public function mount(): void
    {
        if(!session('selected_campaign_id')) {
            session(['selected_campaign_id' => $this->campaigns->first()?->id]);
            $campaign = $this->campaigns->first();
            $campaignId = $campaign?->id;
            session(['selected_campaign_role' => $campaign?->userIsDm() ? 'dm' : 'player']);
            $this->dispatch('campaign-switched', campaignId: $campaignId);
        }
        $sessionCampaignId = session('selected_campaign_id');

        $this->selectedCampaignId = $this->campaigns->firstWhere('id', $sessionCampaignId)?->id
            ?? $this->campaigns->first()?->id;
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

    #[Computed]
    public function selectedCampaign(): ?Campaign
    {
        return $this->campaigns->firstWhere('id', $this->selectedCampaignId);
    }

    public function selectCampaign(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;

        $campaign = $this->campaigns->firstWhere('id', $campaignId);
        session(['selected_campaign_id' => $campaignId]);
        session(['selected_campaign_role' => $campaign?->userIsDm() ? 'dm' : 'player']);

        $this->dispatch('campaign-switched', campaignId: $campaignId);
    }

    public function createCampaign(): void
    {
        $data = $this->validate([
            'newCampaignName' => ['required', 'string', 'max:255'],
            'newCampaignDescription' => ['nullable', 'string', 'max:255'],
        ]);

        $campaign = auth()->user()->owned_campaigns()->create([
            'name' => $data['newCampaignName'],
            'description' => $data['newCampaignDescription'] ?: null,
        ]);

        auth()->user()->campaigns()->attach($campaign->id);

        unset($this->campaigns);

        $this->reset(['newCampaignName', 'newCampaignDescription']);

        $this->selectCampaign($campaign->id);

        Flux::modal('create-campaign')->close();
    }
};
?>

<div class="mx-auto w-full">
    @if ($this->campaigns->isEmpty())
        <flux:modal.trigger name="create-campaign">
            <button type="button" class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-line px-4 py-3.5 text-content-muted transition hover:border-brand-400 hover:text-brand-600">
                <flux:icon.plus class="size-4" />
                <span class="text-sm font-bold">Create your first campaign</span>
            </button>
        </flux:modal.trigger>
    @else
        <flux:dropdown position="bottom" align="start" class="block w-full">
            <button type="button" class="flex w-full items-center justify-between gap-3 rounded-2xl lg:border lg:border-line lg:bg-surface-subtle px-4 py-3 text-left lg:shadow-sm transition hover:border-brand-300">
                <div class="min-w-0">
                    <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Current Campaign</div>
                    <div class="mt-0.5 flex items-center gap-1.5">
                        <span class="truncate text-lg font-bold text-content">{{ $this->selectedCampaign?->name }}</span>
                        <flux:icon.chevron-down class="size-3.5 shrink-0 text-content-muted" />
                    </div>
                </div>

                <flux:avatar size="sm" name="{{ $this->selectedCampaign?->name }}" color="auto" />
            </button>

            <flux:menu class="w-72">
                <div class="px-3 pt-2 pb-1 text-[10px] font-bold tracking-widest text-content-faint uppercase">Switch Campaign</div>

                @foreach ($this->campaigns as $campaign)
                    <flux:menu.item wire:click="selectCampaign({{ $campaign->id }})" wire:key="campaign-switch-{{ $campaign->id }}">
                        <div class="flex w-full items-center gap-3">
                            <flux:avatar size="xs" name="{{ $campaign->name }}" color="auto" />

                            <div class="min-w-0 flex-1 text-left">
                                <div class="truncate text-sm font-bold text-content">{{ $campaign->name }}</div>
                                <div class="text-xs text-content-muted">{{ $campaign->owner_id === auth()->id() ? 'Dungeon Master' : 'Player' }}</div>
                            </div>

                            @if ($campaign->id === $this->selectedCampaignId)
                                <flux:icon.check class="size-4 shrink-0 text-brand-600" />
                            @endif
                        </div>
                    </flux:menu.item>
                @endforeach

                <flux:menu.separator />

                <flux:modal.trigger name="create-campaign">
                    <flux:menu.item icon="plus">New campaign</flux:menu.item>
                </flux:modal.trigger>
            </flux:menu>
        </flux:dropdown>
    @endif

    <flux:modal name="create-campaign" class="md:w-96">
        <form wire:submit="createCampaign" class="space-y-6">
            <div>
                <flux:heading size="lg">New Campaign</flux:heading>
                <flux:text class="mt-2">Start a fresh campaign to track characters, shops, and journals.</flux:text>
            </div>

            <flux:input wire:model="newCampaignName" label="Campaign name" placeholder="e.g. Port Namas" />

            <flux:textarea wire:model="newCampaignDescription" label="Description" placeholder="Optional summary for your table" rows="3" />

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Create campaign</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

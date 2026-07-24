<?php

use App\Models\Campaign;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $campaignId = null;

    public string $name = '';

    public string $description = '';

    public string $image = '';

    public string $status = 'active';

    public ?string $nextSessionDate = null;

    public function mount(): void
    {
        $this->campaignId = session('selected_campaign_id');

        $this->loadCampaign();
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->campaignId = $campaignId;

        $this->resetValidation();
        $this->loadCampaign();
    }

    protected function loadCampaign(): void
    {
        $campaign = $this->campaign;

        if (! $campaign) {
            return;
        }

        $this->name = $campaign->name;
        $this->description = $campaign->description ?? '';
        $this->image = $campaign->image ?? '';
        $this->status = $campaign->status;
        $this->nextSessionDate = $campaign->next_session_date?->format('Y-m-d');
    }

    /**
     * Only campaigns the user belongs to or owns are considered accessible.
     */
    #[Computed]
    public function campaign(): ?Campaign
    {
        if (! $this->campaignId) {
            return null;
        }

        $user = auth()->user();

        return $user->campaigns->merge($user->owned_campaigns)
            ->unique('id')
            ->firstWhere('id', $this->campaignId);
    }

    #[Computed]
    public function isOwner(): bool
    {
        return (bool) $this->campaign && $this->campaign->owner_id === auth()->id();
    }

    public function updateDetails(): void
    {
        abort_unless($this->isOwner, 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'string', 'max:2048'],
            'status' => ['required', 'in:active,inactive'],
            'nextSessionDate' => ['nullable', 'date'],
        ]);

        $this->campaign->update([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'image' => $data['image'] ?: null,
            'status' => $data['status'],
            'next_session_date' => $data['nextSessionDate'] ?: null,
        ]);

        unset($this->campaign);

        Flux::toast('Campaign details updated.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Campaign Details</div>

    @if (! $this->campaign)
        <div class="mt-3 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its details.
        </div>
    @else
        <div class="mt-3 flex items-center gap-4">
            @if ($this->campaign->image)
                <img src="{{ $this->campaign->image }}" alt="{{ $this->campaign->name }}" class="size-16 shrink-0 rounded-xl border border-line object-cover" />
            @else
                <flux:avatar size="xl" name="{{ $this->campaign->name }}" color="auto" />
            @endif

            <div class="min-w-0">
                <flux:heading size="lg">{{ $this->campaign->name }}</flux:heading>

                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-content-muted">
                    <span>DM: {{ $this->campaign->owner?->name }}</span>
                    <span>·</span>
                    <flux:badge size="sm" :color="$this->campaign->status === 'active' ? 'green' : 'zinc'">
                        {{ ucfirst($this->campaign->status) }}
                    </flux:badge>
                </div>
            </div>
        </div>

        @if ($this->campaign->description)
            <p class="mt-4 text-sm text-content">{{ $this->campaign->description }}</p>
        @endif

        <div class="mt-4 text-sm text-content-muted">
            Next session: {{ $this->campaign->next_session_date?->format('D, M j, Y') ?? 'Not scheduled' }}
        </div>

        @if ($this->isOwner)
            <flux:separator class="my-5" />

            <form wire:submit="updateDetails" class="space-y-4">
                <flux:input wire:model="name" label="Campaign name" />

                <flux:textarea wire:model="description" label="Description" rows="3" />

                <flux:input wire:model="image" label="Image URL" placeholder="https://..." />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                    </flux:select>

                    <flux:input wire:model="nextSessionDate" type="date" label="Next session" />
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        @endif
    @endif
</div>

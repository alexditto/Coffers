<?php

use App\Models\Campaign;
use Flux\Flux;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $campaignId = null;

    public string $name = '';

    public string $description = '';

    public $image = null;

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
        $this->image = null;
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
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', 'in:active,inactive'],
            'nextSessionDate' => ['nullable', 'date'],
        ]);

        $imageUrl = $this->image
            ? Storage::disk('s3')->url(Image::fromUpload($this->image)->scale(800, 800)->store('campaigns', 's3'))
            : $this->campaign->image;

        $this->campaign->update([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'image' => $imageUrl,
            'status' => $data['status'],
            'next_session_date' => $data['nextSessionDate'] ?: null,
        ]);

        unset($this->campaign);

        Flux::toast('Campaign details updated.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm dark:bg-gray-800">
    <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Campaign Details</div>

    @if (! $this->campaign)
        <div class="mt-3 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted dark:text-content-muted-dark">
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
            <p class="mt-4 text-sm text-content dark:text-white">{{ $this->campaign->description }}</p>
        @endif

        <div class="mt-4 text-sm text-content-muted">
            Next session: {{ $this->campaign->next_session_date?->format('D, M j, Y') ?? 'Not scheduled' }}
        </div>

        @if ($this->isOwner)
            <flux:separator class="my-5" />

            <form wire:submit="updateDetails" class="space-y-4">
                <flux:input wire:model="name" label="Campaign name" />

                <flux:textarea wire:model="description" label="Description" rows="3" />

                <flux:field>
                    <flux:label>Image</flux:label>

                    <div class="flex items-center gap-3">
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="size-14 shrink-0 rounded-xl border border-line object-cover" />
                        @elseif ($this->campaign->image)
                            <img src="{{ $this->campaign->image }}" alt="Current image" class="size-14 shrink-0 rounded-xl border border-line object-cover" />
                        @else
                            <div class="flex size-14 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-line text-content-faint">
                                <flux:icon.photo class="size-5" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <input
                                type="file"
                                wire:model="image"
                                accept="image/*"
                                class="block w-full text-sm text-content-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-700"
                            />
                            <div wire:loading wire:target="image" class="mt-1 text-xs text-content-muted">Uploading…</div>
                        </div>
                    </div>

                    <flux:error name="image" />
                </flux:field>

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

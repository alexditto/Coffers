<?php

use App\Models\Campaign;
use App\Models\Journal;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    const TYPES = [
        'npc' => 'NPCs',
        'quest' => 'Quests',
        'place' => 'Places',
        'lore' => 'Lore',
    ];

    public ?int $selectedCampaignId = null;

    public string $typeFilter = 'npc';

    public ?int $editingEntryId = null;

    public string $entryTitle = '';

    public string $entryType = 'npc';

    public string $entryContent = '';

    public string $entryImage = '';

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;
        $this->typeFilter = 'npc';

        unset($this->campaign, $this->entries);
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

    #[Computed]
    public function isOwner(): bool
    {
        return (bool) $this->campaign && $this->campaign->owner_id === auth()->id();
    }

    public function typeOptions(): array
    {
        return self::TYPES;
    }

    /**
     * @return Collection<int, Journal>
     */
    #[Computed]
    public function entries(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        $query = $this->campaign->journals()->where('type', $this->typeFilter)->orderBy('title');

        // Players only ever receive entries the DM has revealed - hidden entries
        // never reach the response, they aren't just visually hidden client-side.
        if (! $this->isOwner) {
            $query->where('revealed', true);
        }

        return $query->get();
    }

    public function setTypeFilter(string $type): void
    {
        $this->typeFilter = $type;

        unset($this->entries);
    }

    public function toggleReveal(int $entryId): void
    {
        abort_unless($this->isOwner, 403);

        $entry = $this->campaign->journals()->where('id', $entryId)->first();

        if (! $entry) {
            return;
        }

        $entry->update(['revealed' => ! $entry->revealed]);

        unset($this->entries);
    }

    public function editEntry(int $entryId): void
    {
        abort_unless($this->isOwner, 403);

        $entry = $this->campaign->journals()->where('id', $entryId)->first();

        if (! $entry) {
            return;
        }

        $this->editingEntryId = $entry->id;
        $this->entryTitle = $entry->title;
        $this->entryType = $entry->type;
        $this->entryContent = $entry->content;
        $this->entryImage = $entry->image ?? '';

        Flux::modal('edit-entry')->show();
    }

    public function newEntry(): void
    {
        abort_unless($this->isOwner, 403);

        $this->editingEntryId = null;
        $this->entryTitle = '';
        $this->entryType = $this->typeFilter;
        $this->entryContent = '';
        $this->entryImage = '';

        Flux::modal('edit-entry')->show();
    }

    public function saveEntry(): void
    {
        abort_unless($this->isOwner, 403);

        $data = $this->validate([
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryType' => ['required', Rule::in(array_keys(self::TYPES))],
            'entryContent' => ['required', 'string', 'max:5000'],
            'entryImage' => ['nullable', 'string', 'max:2048'],
        ]);

        if ($this->editingEntryId) {
            $entry = $this->campaign->journals()->where('id', $this->editingEntryId)->firstOrFail();

            $entry->update([
                'title' => $data['entryTitle'],
                'type' => $data['entryType'],
                'content' => $data['entryContent'],
                'image' => $data['entryImage'] ?: null,
            ]);

            Flux::toast('Entry updated.', variant: 'success');
        } else {
            $this->campaign->journals()->create([
                'title' => $data['entryTitle'],
                'type' => $data['entryType'],
                'content' => $data['entryContent'],
                'image' => $data['entryImage'] ?: null,
                'revealed' => false,
            ]);

            $this->typeFilter = $data['entryType'];

            Flux::toast('Entry created.', variant: 'success');
        }

        unset($this->entries);

        Flux::modal('edit-entry')->close();
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <flux:heading size="lg">Journal</flux:heading>

    @if (! $this->campaign)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its journal.
        </div>
    @else
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($this->typeOptions() as $value => $label)
                <button
                    type="button"
                    wire:click="setTypeFilter('{{ $value }}')"
                    @class([
                        'rounded-full px-3 py-1.5 text-xs font-bold transition',
                        'bg-brand-600 text-white' => $typeFilter === $value,
                        'border border-line text-content-muted' => $typeFilter !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-2">
            @forelse ($this->entries as $entry)
                <div wire:key="entry-{{ $entry->id }}" @class([
                    'flex items-center gap-3 rounded-xl border border-line p-3',
                    'bg-canvas' => $this->isOwner && ! $entry->revealed,
                ])>
                    @if ($entry->image)
                        <img src="{{ $entry->image }}" alt="{{ $entry->title }}" class="size-10 shrink-0 rounded-full border border-line object-cover" />
                    @else
                        <flux:avatar size="sm" circle name="{{ $entry->title }}" color="auto" />
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-content">{{ $entry->title }}</div>

                        @if ($this->isOwner)
                            <div class="text-xs text-content-muted">{{ $entry->revealed ? 'Revealed to party' : 'Hidden — tap to reveal' }}</div>
                        @elseif ($entry->content)
                            <p class="mt-0.5 line-clamp-2 text-xs text-content-muted">{{ $entry->content }}</p>
                        @endif
                    </div>

                    @if ($this->isOwner)
                        <flux:button
                            size="sm"
                            :variant="$entry->revealed ? 'primary' : 'ghost'"
                            wire:click="toggleReveal({{ $entry->id }})"
                        >
                            {{ $entry->revealed ? 'Hide' : 'Reveal' }}
                        </flux:button>

                        <flux:button size="sm" variant="ghost" wire:click="editEntry({{ $entry->id }})">Edit</flux:button>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                    No entries yet.
                </div>
            @endforelse
        </div>

        @if ($this->isOwner)
            <flux:button variant="primary" class="mt-5 w-full" wire:click="newEntry">
                + New entry
            </flux:button>

            <flux:modal name="edit-entry" class="md:w-96">
                <form wire:submit="saveEntry" class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ $editingEntryId ? 'Edit Entry' : 'New Entry' }}</flux:heading>
                        <flux:text class="mt-2">New entries start hidden until you reveal them to the party.</flux:text>
                    </div>

                    <flux:input wire:model="entryTitle" label="Title" placeholder="e.g. Dockmaster Hale" />

                    <flux:select wire:model="entryType" label="Type">
                        @foreach ($this->typeOptions() as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:textarea wire:model="entryContent" label="Details" rows="4" />

                    <flux:input wire:model="entryImage" label="Image URL" placeholder="https://..." />

                    <div class="flex gap-2">
                        <flux:spacer />

                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button type="submit" variant="primary">{{ $editingEntryId ? 'Save changes' : 'Create entry' }}</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endif
    @endif
</div>

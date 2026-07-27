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
    const FILTERS = [
        'all' => 'All',
        'npc' => 'NPCs',
        'quest' => 'Quests',
        'place' => 'Places',
        'lore' => 'Lore',
    ];

    const ENTRY_TYPES = [
        'npc' => 'NPCs',
        'quest' => 'Quests',
        'place' => 'Places',
        'lore' => 'Lore',
    ];

    public ?int $selectedCampaignId = null;

    public string $typeFilter = 'all';

    public string $search = '';

    public ?int $viewingEntryId = null;

    public ?int $editingEntryId = null;

    public ?int $deletingEntryId = null;

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
        $this->typeFilter = 'all';
        $this->search = '';

        unset($this->campaign, $this->entries);
    }

    /**
     * Only campaigns the user belongs to or owns are considered accessible.
     */
    #[Computed]
    public function campaign(): ?Campaign
    {
        if (!$this->selectedCampaignId) {
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
        return (bool)$this->campaign && $this->campaign->owner_id === auth()->id();
    }

    public function filterOptions(): array
    {
        return self::FILTERS;
    }

    public function entryTypeOptions(): array
    {
        return self::ENTRY_TYPES;
    }

    /**
     * Whether the current user can reveal/hide, edit, or otherwise manage this entry -
     * the DM can manage every entry, a player can only manage entries they authored.
     */
    public function canManage(Journal $entry): bool
    {
        return $this->isOwner || $entry->user_id === auth()->id();
    }

    /**
     * Whether the current user is allowed to see this entry at all - the same rule
     * used to filter the entries() list, re-checked here to guard direct calls.
     */
    public function canView(Journal $entry): bool
    {
        return $this->isOwner || $entry->revealed || $entry->user_id === auth()->id();
    }

    /**
     * @return Collection<int, Journal>
     */
    #[Computed]
    public function entries(): Collection
    {
        if (!$this->campaign) {
            return collect();
        }

        $query = $this->campaign->journals()
            ->when($this->typeFilter !== 'all', fn($query) => $query->where('type', $this->typeFilter))
            ->when(
                $this->search !== '',
                fn($query) => $query->where(
                    fn($query) => $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('content', 'like', '%' . $this->search . '%')
                )
            )
            ->orderBy('title');

        // Players only ever receive entries that have been shared with the party, plus
        // their own private entries - hidden entries never reach the response, they
        // aren't just visually hidden client-side.
        if (!$this->isOwner) {
            $query->where(fn($query) => $query->where('revealed', true)->orWhere('user_id', auth()->id()));
        }

        return $query->get();
    }

    public function setTypeFilter(string $type): void
    {
        $this->typeFilter = $type;

        unset($this->entries);
    }

    #[Computed]
    public function viewingEntry(): ?Journal
    {
        if (!$this->viewingEntryId || !$this->campaign) {
            return null;
        }

        return $this->campaign->journals()->where('id', $this->viewingEntryId)->with('user')->first();
    }

    #[Computed]
    public function deletingEntry(): ?Journal
    {
        if (!$this->deletingEntryId || !$this->campaign) {
            return null;
        }

        return $this->campaign->journals()->where('id', $this->deletingEntryId)->first();
    }

    public function viewEntry(int $entryId): void
    {
        $entry = $this->campaign->journals()->where('id', $entryId)->first();

        if (!$entry) {
            return;
        }

        abort_unless($this->canView($entry), 403);

        $this->viewingEntryId = $entry->id;

        Flux::modal('entry-details')->show();
    }

    public function toggleReveal(int $entryId): void
    {
        $entry = $this->campaign->journals()->where('id', $entryId)->first();

        if (!$entry) {
            return;
        }

        abort_unless($this->canManage($entry), 403);

        $entry->update(['revealed' => !$entry->revealed]);

        unset($this->entries);
    }

    public function editEntry(int $entryId): void
    {
        $entry = $this->campaign->journals()->where('id', $entryId)->first();

        if (!$entry) {
            return;
        }

        abort_unless($this->canManage($entry), 403);

        $this->editingEntryId = $entry->id;
        $this->entryTitle = $entry->title;
        $this->entryType = $entry->type;
        $this->entryContent = $entry->content;
        $this->entryImage = $entry->image ?? '';

        Flux::modal('edit-entry')->show();
    }

    public function newEntry(): void
    {
        abort_unless($this->campaign, 403);

        $this->editingEntryId = null;
        $this->entryTitle = '';
        $this->entryType = $this->typeFilter === 'all' ? 'lore' : $this->typeFilter;
        $this->entryContent = '';
        $this->entryImage = '';

        Flux::modal('edit-entry')->show();
    }

    public function saveEntry(): void
    {
        abort_unless($this->campaign, 403);

        if ($this->editingEntryId) {
            $entry = $this->campaign->journals()->where('id', $this->editingEntryId)->firstOrFail();

            abort_unless($this->canManage($entry), 403);
        }

        $data = $this->validate([
            'entryTitle' => ['required', 'string', 'max:255'],
            'entryType' => ['required', Rule::in(array_keys(self::ENTRY_TYPES))],
            'entryContent' => ['required', 'string', 'max:5000'],
            'entryImage' => ['nullable', 'string', 'max:2048'],
        ]);

        if ($this->editingEntryId) {
            $entry->update([
                'title' => $data['entryTitle'],
                'type' => $data['entryType'],
                'content' => $data['entryContent'],
                'image' => $data['entryImage'] ?: null,
            ]);

            Flux::toast('Entry updated.', variant: 'success');
        } else {
            $this->campaign->journals()->create([
                'user_id' => auth()->id(),
                'title' => $data['entryTitle'],
                'type' => $data['entryType'],
                'content' => $data['entryContent'],
                'image' => $data['entryImage'] ?: null,
                'revealed' => false,
            ]);

            if ($this->typeFilter !== 'all') {
                $this->typeFilter = $data['entryType'];
            }

            Flux::toast('Entry created.', variant: 'success');
        }

        unset($this->entries);

        Flux::modal('edit-entry')->close();
    }

    public function confirmDeleteEntry(): void
    {
        if (!$this->editingEntryId) {
            return;
        }

        $entry = $this->campaign->journals()->where('id', $this->editingEntryId)->first();

        if (!$entry) {
            return;
        }

        abort_unless($this->canManage($entry), 403);

        $this->deletingEntryId = $entry->id;

        Flux::modal('edit-entry')->close();
        Flux::modal('confirm-delete-entry')->show();
    }

    public function deleteEntry(): void
    {
        $entry = $this->deletingEntry;

        if (!$entry) {
            return;
        }

        abort_unless($this->canManage($entry), 403);

        $name = $entry->title;
        $entry->delete();

        $this->deletingEntryId = null;

        unset($this->entries);

        Flux::modal('confirm-delete-entry')->close();

        Flux::toast($name . ' deleted.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm dark:bg-gray-800">
    <flux:heading size="lg">Journal</flux:heading>

    @if (! $this->campaign)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its journal.
        </div>
    @else
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search entries..." clearable
                    class="mt-4"/>

        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($this->filterOptions() as $value => $label)
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
                @php $canManage = $this->canManage($entry); @endphp

                <div wire:key="entry-{{ $entry->id }}" @class([
                    'flex items-center gap-3 rounded-xl border border-line p-3 dark:bg-gray-700',
                    'bg-canvas' => $canManage && ! $entry->revealed,
                ])>
                    <button type="button" wire:click="viewEntry({{ $entry->id }})"
                            class="flex min-w-0 flex-1 items-center gap-3 text-left cursor-pointer">
                        @if ($entry->image)
                            <img src="{{ $entry->image }}" alt="{{ $entry->title }}"
                                 class="size-10 shrink-0 rounded-full border border-line object-cover"/>
                        @else
                            <flux:avatar size="sm" circle name="{{ $entry->title }}" color="auto"/>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content dark:text-white">{{ $entry->title }}</div>

                            @if ($canManage)
                                <div class="text-xs text-content-muted dark:text-gray-400">
                                    @if ($this->isOwner)
                                        {{ $entry->revealed ? 'Revealed to party' : 'Hidden — tap to view' }}
                                    @else
                                        {{ $entry->revealed ? 'Shared with party' : 'Private — tap to view' }}
                                    @endif
                                </div>
                            @elseif ($entry->content)
                                <p class="mt-0.5 line-clamp-2 text-xs text-content-muted">{{ $entry->content }}</p>
                            @endif
                        </div>
                    </button>

                    @if ($canManage)
                        <flux:button
                            size="sm"
                            :variant="$entry->revealed ? 'primary' : 'ghost'"
                            wire:click="toggleReveal({{ $entry->id }})"
                        >
                            @if ($this->isOwner)
                                {{ $entry->revealed ? 'Hide' : 'Reveal' }}
                            @else
                                {{ $entry->revealed ? 'Private' : 'Share' }}
                            @endif
                        </flux:button>

                        <flux:button size="sm" variant="ghost" wire:click="editEntry({{ $entry->id }})">Edit
                        </flux:button>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                    @if ($search !== '')
                        No entries match "{{ $search }}".
                    @else
                        No entries yet.
                    @endif
                </div>
            @endforelse
        </div>

        <flux:button variant="primary" class="mt-5 w-full" wire:click="newEntry">
            + New entry
        </flux:button>

        <flux:modal name="edit-entry" class="md:w-96">
            <form wire:submit="saveEntry" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingEntryId ? 'Edit Entry' : 'New Entry' }}</flux:heading>
                    <flux:text class="mt-2">New entries start private until you share them with the party.</flux:text>
                </div>

                <flux:input wire:model="entryTitle" label="Title" placeholder="e.g. Dockmaster Hale"/>

                <flux:select wire:model="entryType" label="Type">
                    @foreach ($this->entryTypeOptions() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="entryContent" label="Details" rows="4"/>

                <flux:input wire:model="entryImage" label="Image URL" placeholder="https://..."/>

                @if ($editingEntryId)
                    <flux:button type="button" variant="danger" class="w-full" wire:click="confirmDeleteEntry">
                        Delete entry
                    </flux:button>
                @endif

                <div class="flex gap-2">
                    <flux:spacer/>

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit"
                                 variant="primary">{{ $editingEntryId ? 'Save changes' : 'Create entry' }}</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="confirm-delete-entry" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete entry?</flux:heading>
                    <flux:text class="mt-2">
                        This will permanently delete {{ $this->deletingEntry?->title }}. This can't be undone.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer/>

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="deleteEntry">Delete</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="entry-details" class="md:w-96">
            @if ($this->viewingEntry)
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        @if ($this->viewingEntry->image)
                            <img src="{{ $this->viewingEntry->image }}" alt="{{ $this->viewingEntry->title }}"
                                 class="size-12 shrink-0 rounded-full border border-line object-cover"/>
                        @else
                            <flux:avatar size="lg" circle name="{{ $this->viewingEntry->title }}" color="auto"/>
                        @endif

                        <div class="min-w-0 flex-1">
                            <flux:heading size="lg">{{ $this->viewingEntry->title }}</flux:heading>
                            <flux:text
                                class="text-content-muted">{{ $this->entryTypeOptions()[$this->viewingEntry->type] ?? $this->viewingEntry->type }}</flux:text>
                        </div>
                    </div>

                    <flux:text>{{ $this->viewingEntry->content }}</flux:text>


                    <flux:text class="text-content-muted">Written by {{ $this->viewingEntry->user->name }}</flux:text>

                    <div class="flex gap-2">
                        <flux:spacer/>

                        <flux:modal.close>
                            <flux:button variant="ghost">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @endif
        </flux:modal>
    @endif
</div>

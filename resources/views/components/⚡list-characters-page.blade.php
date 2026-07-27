<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\CharacterStatus;
use App\Models\Inventory;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    const DEFAULT_MAX_HEALTH = 100;

    public ?int $selectedCampaignId = null;

    public ?int $editingCharacterId = null;

    public int $editHealth = 0;

    public array $editConditionIds = [];

    public int $editGold = 0;

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;

        if(session('selected_campaign_role') !== 'dm'){
            redirect('/character');
        }

        unset($this->campaign, $this->characters);
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

    /**
     * @return Collection<int, Character>
     */
    #[Computed]
    public function characters(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        return $this->campaign->characters()->with(['character_sheet.statuses', 'inventory'])->orderBy('name')->get();
    }

    /**
     * @return Collection<int, CharacterStatus>
     */
    #[Computed]
    public function conditionOptions(): Collection
    {
        return CharacterStatus::orderBy('name')->get();
    }

    public function healthPercent(Character $character): int
    {
        $sheet = $character->character_sheet;

        if (! $sheet || ! $sheet->total_health) {
            return 0;
        }

        return (int) min(100, max(0, round(($sheet->health / $sheet->total_health) * 100)));
    }

    public function editCharacter(int $characterId): void
    {
        abort_unless($this->isOwner, 403);

        $character = $this->characters->firstWhere('id', $characterId);

        if (! $character) {
            return;
        }

        $this->editingCharacterId = $character->id;
        $this->editHealth = $character->character_sheet?->health ?? 0;
        $this->editConditionIds = $character->character_sheet?->statuses->pluck('id')->all() ?? [];
        $this->editGold = $character->inventory?->gold ?? 0;

        Flux::modal('edit-character')->show();
    }

    public function saveCharacter(): void
    {
        abort_unless($this->isOwner, 403);

        $character = $this->characters->firstWhere('id', $this->editingCharacterId);

        if (! $character) {
            return;
        }

        $maxHealth = $character->character_sheet?->total_health ?? self::DEFAULT_MAX_HEALTH;

        $data = $this->validate([
            'editHealth' => ['required', 'integer', 'min:0', 'max:'.$maxHealth],
            'editConditionIds' => ['array'],
            'editConditionIds.*' => ['integer', 'exists:character_statuses,id'],
            'editGold' => ['required', 'integer', 'min:0'],
        ]);

        $sheet = $character->character_sheet;

        if ($sheet) {
            $sheet->update(['health' => $data['editHealth']]);
        } else {
            $sheet = CharacterSheet::create([
                'character_id' => $character->id,
                'total_health' => $maxHealth,
                'health' => $data['editHealth'],
            ]);

            $character->update(['character_sheet_id' => $sheet->id]);
        }

        $sheet->statuses()->sync($data['editConditionIds'] ?? []);

        if ($character->inventory) {
            $character->inventory->update(['gold' => $data['editGold']]);
        } else {
            $inventory = Inventory::create([
                'character_id' => $character->id,
                'gold' => $data['editGold'],
            ]);

            $character->update(['inventory_id' => $inventory->id]);
        }

        unset($this->characters);

        Flux::modal('edit-character')->close();

        Flux::toast($character->name.' updated.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    @if (! $this->campaign)
        <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its party.
        </div>
    @else
        <flux:heading size="lg">The Party · {{ $this->characters->count() }}</flux:heading>

        <div class="mt-4 flex flex-col gap-3">
            @forelse ($this->characters as $character)
                @php
                    $sheet = $character->character_sheet;
                    $conditions = $sheet?->statuses ?? collect();
                    $percent = $this->healthPercent($character);
                @endphp

                <div
                    wire:key="character-{{ $character->id }}"
                    @if ($this->isOwner) wire:click="editCharacter({{ $character->id }})" @endif
                    @class([
                        'rounded-xl border border-line bg-surface p-3 transition',
                        'cursor-pointer hover:border-brand-300 hover:shadow-sm' => $this->isOwner,
                    ])
                >
                    <div class="flex items-center gap-3">
                        @if ($character->image)
                            <img src="{{ $character->image }}" alt="{{ $character->name }}" class="size-12 shrink-0 rounded-xl border border-line object-cover" />
                        @else
                            <flux:avatar size="lg" name="{{ $character->name }}" color="auto" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-bold text-content">{{ $character->name }}</span>

                                <div class="flex flex-wrap justify-end gap-1">
                                    @forelse ($conditions as $condition)
                                        <flux:badge size="sm" color="amber">{{ strtoupper($condition->name) }}</flux:badge>
                                    @empty
                                        <flux:badge size="sm" color="zinc">HEALTHY</flux:badge>
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-0.5 text-xs text-content-muted">
                                {{ $sheet?->class ? ucfirst($sheet->class) : 'Unknown class' }} · Lvl {{ $sheet?->level ?? 1 }}
                            </div>

                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-canvas">
                                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $percent }}%"></div>
                                </div>

                                <span class="shrink-0 text-[11px] font-semibold text-content-muted">{{ $sheet?->health ?? 0 }}/{{ $sheet?->total_health ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                    No characters in this campaign yet.
                </div>
            @endforelse
        </div>

        @if ($this->isOwner)
            <flux:modal name="edit-character" class="md:w-96">
                <form wire:submit="saveCharacter" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Edit Character</flux:heading>
                        <flux:text class="mt-2">Update health, status, and gold.</flux:text>
                    </div>

                    <flux:input wire:model="editHealth" type="number" min="0" label="Health" />

                    <x-condition-multi-select
                        model="editConditionIds"
                        :options="$this->conditionOptions"
                        :selected="$editConditionIds"
                    />

                    <flux:input wire:model="editGold" type="number" min="0" label="Gold" />

                    <div class="flex gap-2">
                        <flux:spacer />

                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button type="submit" variant="primary">Save changes</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endif
    @endif
</div>

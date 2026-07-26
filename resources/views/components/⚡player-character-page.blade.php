<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    const STATUSES = [
        'none' => 'Healthy',
        'poisoned' => 'Poisoned',
        'blinded' => 'Blinded',
        'deafened' => 'Deafened',
        'paralyzed' => 'Paralyzed',
        'stunned' => 'Stunned',
    ];

    const ALIGNMENTS = [
        'lawful good' => 'Lawful Good',
        'neutral good' => 'Neutral Good',
        'chaotic good' => 'Chaotic Good',
        'lawful neutral' => 'Lawful Neutral',
        'true neutral' => 'True Neutral',
        'chaotic neutral' => 'Chaotic Neutral',
        'lawful evil' => 'Lawful Evil',
        'neutral evil' => 'Neutral Evil',
        'chaotic evil' => 'Chaotic Evil',
    ];

    public ?int $selectedCampaignId = null;

    public int $quickHealth = 0;

    public string $quickStatus = 'none';

    public string $sheetDescription = '';

    public string $sheetClass = '';

    public string $sheetRace = '';

    public int $sheetLevel = 1;

    public string $sheetAlignment = 'true neutral';

    public string $sheetBackground = '';

    public int $sheetTotalHealth = 10;

    public int $sheetAc = 10;

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;
        if(session('selected_campaign_role') !== 'player'){
            redirect('/characters');
        }

        unset($this->campaign, $this->character, $this->partyMembers);
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
     * The current user's own character in the selected campaign.
     */
    #[Computed]
    public function character(): ?Character
    {
        if (! $this->campaign) {
            return null;
        }

        return auth()->user()->characters()
            ->where('campaign_id', $this->campaign->id)
            ->with(['character_sheet', 'inventory'])
            ->first();
    }

    /**
     * Every other character in the campaign.
     *
     * @return Collection<int, Character>
     */
    #[Computed]
    public function partyMembers(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        return $this->campaign->characters()
            ->when($this->character, fn ($query) => $query->where('id', '!=', $this->character->id))
            ->with('character_sheet')
            ->orderBy('name')
            ->get();
    }

    public function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? self::STATUSES['none'];
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return self::STATUSES;
    }

    /**
     * @return array<string, string>
     */
    public function alignmentOptions(): array
    {
        return self::ALIGNMENTS;
    }

    public function openQuickEdit(): void
    {
        if (! $this->character) {
            return;
        }

        $sheet = $this->character->character_sheet;

        $this->quickHealth = $sheet?->health ?? 0;
        $this->quickStatus = $sheet?->status ?? 'none';

        Flux::modal('quick-edit')->show();
    }

    public function saveQuickEdit(): void
    {
        if (! $this->character) {
            return;
        }

        $maxHealth = $this->character->character_sheet?->total_health ?? 100;

        $data = $this->validate([
            'quickHealth' => ['required', 'integer', 'min:0', 'max:'.$maxHealth],
            'quickStatus' => ['required', Rule::in(array_keys(self::STATUSES))],
        ]);

        if ($this->character->character_sheet) {
            $this->character->character_sheet->update([
                'health' => $data['quickHealth'],
                'status' => $data['quickStatus'],
            ]);
        } else {
            $sheet = CharacterSheet::create([
                'character_id' => $this->character->id,
                'total_health' => $maxHealth,
                'health' => $data['quickHealth'],
                'status' => $data['quickStatus'],
            ]);

            $this->character->update(['character_sheet_id' => $sheet->id]);
        }

        unset($this->character);

        Flux::modal('quick-edit')->close();

        Flux::toast('Health and condition updated.', variant: 'success');
    }

    public function openSheetEdit(): void
    {
        if (! $this->character) {
            return;
        }

        $sheet = $this->character->character_sheet;

        $this->sheetDescription = $sheet?->description ?? '';
        $this->sheetClass = $sheet?->class ?? '';
        $this->sheetRace = $sheet?->race ?? '';
        $this->sheetLevel = $sheet?->level ?? 1;
        $this->sheetAlignment = $sheet?->alignment ?? 'true neutral';
        $this->sheetBackground = $sheet?->background ?? '';
        $this->sheetTotalHealth = $sheet?->total_health ?? 10;
        $this->sheetAc = $sheet?->ac ?? 10;

        Flux::modal('sheet-edit')->show();
    }

    public function saveSheetEdit(): void
    {
        if (! $this->character) {
            return;
        }

        $data = $this->validate([
            'sheetDescription' => ['nullable', 'string', 'max:1000'],
            'sheetClass' => ['nullable', 'string', 'max:255'],
            'sheetRace' => ['nullable', 'string', 'max:255'],
            'sheetLevel' => ['required', 'integer', 'min:1', 'max:20'],
            'sheetAlignment' => ['required', Rule::in(array_keys(self::ALIGNMENTS))],
            'sheetBackground' => ['nullable', 'string', 'max:1000'],
            'sheetTotalHealth' => ['required', 'integer', 'min:1'],
            'sheetAc' => ['required', 'integer', 'min:0'],
        ]);

        $sheet = $this->character->character_sheet;

        // Never let a lowered max health silently push current health above it.
        $health = min($sheet?->health ?? $data['sheetTotalHealth'], $data['sheetTotalHealth']);

        if ($sheet) {
            $sheet->update([
                'description' => $data['sheetDescription'] ?: null,
                'class' => $data['sheetClass'] ?: null,
                'race' => $data['sheetRace'] ?: null,
                'level' => $data['sheetLevel'],
                'alignment' => $data['sheetAlignment'],
                'background' => $data['sheetBackground'] ?: null,
                'total_health' => $data['sheetTotalHealth'],
                'health' => $health,
                'ac' => $data['sheetAc'],
            ]);
        } else {
            $sheet = CharacterSheet::create([
                'character_id' => $this->character->id,
                'description' => $data['sheetDescription'] ?: null,
                'class' => $data['sheetClass'] ?: null,
                'race' => $data['sheetRace'] ?: null,
                'level' => $data['sheetLevel'],
                'alignment' => $data['sheetAlignment'],
                'background' => $data['sheetBackground'] ?: null,
                'total_health' => $data['sheetTotalHealth'],
                'health' => $health,
                'ac' => $data['sheetAc'],
                'status' => 'none',
            ]);

            $this->character->update(['character_sheet_id' => $sheet->id]);
        }

        unset($this->character);

        Flux::modal('sheet-edit')->close();

        Flux::toast('Character sheet updated.', variant: 'success');
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    @if (! $this->campaign)
        <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see your character.
        </div>
    @elseif (! $this->character)
        <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            You don't have a character in this campaign yet.
        </div>
    @else
        @php
            $sheet = $this->character->character_sheet;
            $status = $this->statusLabel($sheet?->status);
        @endphp

        <div class="flex items-center gap-4">
            @if ($this->character->image)
                <img src="{{ $this->character->image }}" alt="{{ $this->character->name }}" class="size-16 shrink-0 rounded-xl border border-line object-cover" />
            @else
                <flux:avatar size="xl" name="{{ $this->character->name }}" color="auto" />
            @endif

            <div class="min-w-0 flex-1">
                <flux:heading size="xl">{{ $this->character->name }}</flux:heading>
                <div class="mt-1 text-sm text-content-muted">
                    {{ $sheet?->race ? ucfirst($sheet->race) : 'Unknown race' }} · Lvl {{ $sheet?->level ?? 1 }}
                </div>
            </div>

            <flux:button size="sm" variant="ghost" wire:click="openQuickEdit">Edit</flux:button>
        </div>

        <div class="mt-5 grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-line py-3 text-center">
                <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">HP</div>
                <div class="mt-1 text-lg font-bold text-content">{{ $sheet?->health ?? 0 }}/{{ $sheet?->total_health ?? 0 }}</div>
            </div>

            <div class="rounded-xl border border-line py-3 text-center">
                <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">AC</div>
                <div class="mt-1 text-lg font-bold text-content">{{ $sheet?->ac ?? 10 }}</div>
            </div>

            <div class="rounded-xl border border-line py-3 text-center">
                <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Gold</div>
                <div class="mt-1 text-lg font-bold text-content">{{ $this->character->inventory?->gold ?? 0 }}</div>
            </div>
        </div>

        <flux:button
            :href="route('inventory')"
            wire:navigate
            variant="ghost"
            size="sm"
            class="mt-3 w-full"
            icon:trailing="chevron-right"
        >
            View Inventory
        </flux:button>

        <div class="mt-5">
            <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Conditions</div>

            <div class="mt-2 flex flex-wrap gap-2">
                <flux:badge size="sm" :color="$status === 'Healthy' ? 'zinc' : 'amber'">{{ strtoupper($status) }}</flux:badge>
            </div>
        </div>

        <div class="mt-3">
            <flux:button size="sm" variant="ghost" wire:click="openSheetEdit">Edit character sheet</flux:button>
        </div>

        <flux:separator class="my-5" />

        <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">My Party · {{ $this->partyMembers->count() }}</div>

        <div class="mt-2 flex flex-col gap-2">
            @forelse ($this->partyMembers as $member)
                <div wire:key="party-member-{{ $member->id }}" class="flex items-center gap-3 rounded-xl border border-line p-3">
                    @if ($member->image)
                        <img src="{{ $member->image }}" alt="{{ $member->name }}" class="size-10 shrink-0 rounded-xl border border-line object-cover" />
                    @else
                        <flux:avatar size="sm" name="{{ $member->name }}" color="auto" />
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-content">{{ $member->name }}</div>
                        <div class="text-xs text-content-muted">
                            {{ $member->character_sheet?->race ? ucfirst($member->character_sheet->race) : 'Unknown race' }}
                            ·
                            {{ $member->character_sheet?->class ? ucfirst($member->character_sheet->class) : 'Unknown class' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-line p-4 text-center text-sm text-content-muted">
                    No other characters in this campaign yet.
                </div>
            @endforelse
        </div>

        <flux:modal name="quick-edit" class="md:w-96">
            <form wire:submit="saveQuickEdit" class="space-y-6">
                <div>
                    <flux:heading size="lg">Quick Edit</flux:heading>
                    <flux:text class="mt-2">Update your health and condition.</flux:text>
                </div>

                <flux:input wire:model="quickHealth" type="number" min="0" label="Health" />

                <flux:select wire:model="quickStatus" label="Condition">
                    @foreach ($this->statusOptions() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="sheet-edit" class="md:w-[28rem]">
            <form wire:submit="saveSheetEdit" class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit Character Sheet</flux:heading>
                    <flux:text class="mt-2">Update your character's details.</flux:text>
                </div>

                <flux:textarea wire:model="sheetDescription" label="Description" rows="3" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="sheetClass" label="Class" placeholder="e.g. Rogue" />
                    <flux:input wire:model="sheetRace" label="Race" placeholder="e.g. Elf" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="sheetLevel" type="number" min="1" max="20" label="Level" />

                    <flux:select wire:model="sheetAlignment" label="Alignment">
                        @foreach ($this->alignmentOptions() as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="sheetBackground" label="Background" rows="3" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="sheetTotalHealth" type="number" min="1" label="Max Health" />
                    <flux:input wire:model="sheetAc" type="number" min="0" label="AC" />
                </div>

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
</div>

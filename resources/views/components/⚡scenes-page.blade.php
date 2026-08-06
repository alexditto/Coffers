<?php

use App\Models\Campaign;
use App\Models\Scene;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $selectedCampaignId = null;

    public ?int $editingSceneId = null;

    public string $sceneName = '';

    public string $sceneContent = '';

    public $sceneImage = null;

    public ?string $currentSceneImageUrl = null;

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;
        if(session('selected_campaign_role') !== 'dm'){
            redirect('/dashboard');
        }

        unset($this->campaign, $this->scenes);
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
     * @return Collection<int, Scene>
     */
    #[Computed]
    public function scenes(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        return $this->campaign->scenes()->orderBy('name')->get();
    }

    public function setActiveScene(int $sceneId): void
    {
        abort_unless($this->isOwner, 403);

        $scene = $this->campaign->scenes()->where('id', $sceneId)->first();

        if (! $scene) {
            return;
        }

        DB::transaction(function () use ($scene) {
            $this->campaign->scenes()->where('id', '!=', $scene->id)->update(['status' => 'inactive']);
            $scene->update(['status' => 'active']);
        });

        unset($this->scenes);

        Flux::toast($scene->name.' is now the active scene.', variant: 'success');
    }

    public function editScene(int $sceneId): void
    {
        abort_unless($this->isOwner, 403);

        $scene = $this->campaign->scenes()->where('id', $sceneId)->first();

        if (! $scene) {
            return;
        }

        $this->editingSceneId = $scene->id;
        $this->sceneName = $scene->name;
        $this->sceneContent = $scene->content ?? '';
        $this->sceneImage = null;
        $this->currentSceneImageUrl = $scene->image;

        Flux::modal('edit-scene')->show();
    }

    public function newScene(): void
    {
        abort_unless($this->isOwner, 403);

        $this->editingSceneId = null;
        $this->sceneName = '';
        $this->sceneContent = '';
        $this->sceneImage = null;
        $this->currentSceneImageUrl = null;

        Flux::modal('edit-scene')->show();
    }

    public function saveScene(): void
    {
        abort_unless($this->isOwner, 403);

        $data = $this->validate([
            'sceneName' => ['required', 'string', 'max:255'],
            'sceneContent' => ['nullable', 'string', 'max:5000'],
            'sceneImage' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->editingSceneId) {
            $scene = $this->campaign->scenes()->where('id', $this->editingSceneId)->firstOrFail();

            $imageUrl = $this->sceneImage
                ? Storage::disk('s3')->url($this->sceneImage->store('scenes', 's3'))
                : $scene->image;

            $scene->update([
                'name' => $data['sceneName'],
                'content' => $data['sceneContent'] ?: null,
                'image' => $imageUrl,
            ]);

            Flux::toast('Scene updated.', variant: 'success');
        } else {
            $hasActiveScene = $this->campaign->scenes()->where('status', 'active')->exists();

            $imageUrl = $this->sceneImage
                ? Storage::disk('s3')->url($this->sceneImage->store('scenes', 's3'))
                : null;

            $this->campaign->scenes()->create([
                'name' => $data['sceneName'],
                'content' => $data['sceneContent'] ?: null,
                'image' => $imageUrl,
                'status' => $hasActiveScene ? 'inactive' : 'active',
            ]);

            Flux::toast('Scene created.', variant: 'success');
        }

        unset($this->scenes);

        Flux::modal('edit-scene')->close();
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Scenes</flux:heading>
        <flux:badge size="sm">DM</flux:badge>
    </div>

    @if (! $this->campaign)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Select or create a campaign to see its scenes.
        </div>
    @elseif (! $this->isOwner)
        <div class="mt-4 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
            Only the Dungeon Master can manage scenes.
        </div>
    @else
        @php
            $activeScene = $this->scenes->firstWhere('status', 'active');
            $otherScenes = $this->scenes->reject(fn ($scene) => $scene->id === $activeScene?->id);
        @endphp

        <div class="mt-4 text-[10px] font-bold tracking-widest text-content-faint uppercase">Active Scene</div>

        @if ($activeScene)
            <div wire:key="active-scene-{{ $activeScene->id }}" class="mt-2 rounded-xl border-2 border-brand-600 bg-surface p-3 shadow-sm dark:bg-gray-700">
                <div class="flex items-center gap-3">
                    @if ($activeScene->image)
                        <img src="{{ $activeScene->image }}" alt="{{ $activeScene->name }}" class="size-12 shrink-0 rounded-xl border border-line object-cover" />
                    @else
                        <flux:avatar size="lg" name="{{ $activeScene->name }}" color="auto" />
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-bold text-content dark:text-white">{{ $activeScene->name }}</span>
                            <flux:badge size="sm" color="green">● Live</flux:badge>
                        </div>

                        @if ($activeScene->content)
                            <p class="mt-1 line-clamp-2 text-xs text-content-muted">{{ $activeScene->content }}</p>
                        @endif
                    </div>
                </div>

                <flux:button size="sm" variant="ghost" class="mt-3 w-full" wire:click="editScene({{ $activeScene->id }})">Edit</flux:button>
            </div>
        @else
            <div class="mt-2 rounded-xl border-2 border-dashed border-line p-4 text-center text-sm text-content-muted">
                No active scene. Set one below to bring it live for your party.
            </div>
        @endif

        <div class="mt-5 text-[10px] font-bold tracking-widest text-content-faint uppercase">Other Scenes</div>

        <div class="mt-2 flex flex-col gap-2">
            @forelse ($otherScenes as $scene)
                <div wire:key="scene-{{ $scene->id }}" class="flex items-center gap-3 rounded-xl border border-line p-3">
                    @if ($scene->image)
                        <img src="{{ $scene->image }}" alt="{{ $scene->name }}" class="size-10 shrink-0 rounded-lg border border-line object-cover" />
                    @else
                        <flux:avatar size="sm" name="{{ $scene->name }}" color="auto" />
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-content dark:text-white">{{ $scene->name }}</div>
                    </div>

                    <flux:button size="sm" variant="ghost" wire:click="editScene({{ $scene->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="setActiveScene({{ $scene->id }})">Set active</flux:button>
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-line p-4 text-center text-sm text-content-muted">
                    No other scenes yet.
                </div>
            @endforelse
        </div>

        <flux:button variant="primary" class="mt-5 w-full" wire:click="newScene">
            + New scene
        </flux:button>

        <flux:modal name="edit-scene" class="md:w-96">
            <form wire:submit="saveScene" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingSceneId ? 'Edit Scene' : 'New Scene' }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ $editingSceneId ? 'Update this scene.' : 'Describe the setting for your party.' }}
                    </flux:text>
                </div>

                <flux:input wire:model="sceneName" label="Scene name" placeholder="e.g. Port Namas — The Docks" />

                <flux:textarea wire:model="sceneContent" label="Notes" rows="4" />

                <flux:field>
                    <flux:label>Image</flux:label>

                    <div class="flex items-center gap-3">
                        @if ($sceneImage)
                            <img src="{{ $sceneImage->temporaryUrl() }}" alt="Preview" class="size-14 shrink-0 rounded-xl border border-line object-cover" />
                        @elseif ($currentSceneImageUrl)
                            <img src="{{ $currentSceneImageUrl }}" alt="Current image" class="size-14 shrink-0 rounded-xl border border-line object-cover" />
                        @else
                            <div class="flex size-14 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-line text-content-faint">
                                <flux:icon.photo class="size-5" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <input
                                type="file"
                                wire:model="sceneImage"
                                accept="image/*"
                                class="block w-full text-sm text-content-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-700"
                            />
                            <div wire:loading wire:target="sceneImage" class="mt-1 text-xs text-content-muted">Uploading…</div>
                        </div>
                    </div>

                    <flux:error name="sceneImage" />
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ $editingSceneId ? 'Save changes' : 'Create scene' }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

<?php

use App\Models\Campaign;
use App\Models\Item;
use App\Models\Shop;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    const STATUSES = [
        'open' => 'Open',
        'closed' => 'Closed',
        'draft' => 'Draft',
        'hidden' => 'Hidden',
    ];

    public ?int $selectedCampaignId = null;

    public string $statusFilter = 'all';

    public ?int $editingShopId = null;

    public string $shopName = '';

    public string $shopDescription = '';

    public string $shopImage = '';

    public string $shopStatus = 'draft';

    public ?int $managingStockShopId = null;

    public string $newStockItemId = '';

    public int $newStockPrice = 0;

    public int $newStockQuantity = 1;

    public function mount(): void
    {
        $this->selectedCampaignId = session('selected_campaign_id');
    }

    #[On('campaign-switched')]
    public function onCampaignSwitched(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;
        $this->statusFilter = 'all';

        if(session('selected_campaign_role') !== 'player'){
            redirect('/dashboard');
        }

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

    #[Computed]
    public function isOwner(): bool
    {
        return (bool) $this->campaign && $this->campaign->owner_id === auth()->id();
    }

    /**
     * @return Collection<int, Shop>
     */
    #[Computed]
    public function shops(): Collection
    {
        if (! $this->campaign) {
            return collect();
        }

        $query = $this->campaign->shops()->withCount('stock')->orderBy('name');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->get();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;

        unset($this->shops);
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return self::STATUSES;
    }

    public function openShop(int $shopId): void
    {
        $this->updateShopStatus($shopId, 'open');
    }

    public function closeShop(int $shopId): void
    {
        $this->updateShopStatus($shopId, 'closed');
    }

    protected function updateShopStatus(int $shopId, string $status): void
    {
        abort_unless($this->isOwner, 403);

        $shop = $this->campaign->shops()->where('shops.id', $shopId)->first();

        if (! $shop) {
            return;
        }

        $shop->update(['status' => $status]);

        unset($this->shops);
    }

    public function editShop(int $shopId): void
    {
        abort_unless($this->isOwner, 403);

        $shop = $this->campaign->shops()->where('shops.id', $shopId)->first();

        if (! $shop) {
            return;
        }

        $this->editingShopId = $shop->id;
        $this->shopName = $shop->name;
        $this->shopDescription = $shop->description ?? '';
        $this->shopImage = $shop->image ?? '';
        $this->shopStatus = $shop->status;

        Flux::modal('edit-shop')->show();
    }

    public function newShop(): void
    {
        abort_unless($this->isOwner, 403);

        $this->editingShopId = null;
        $this->shopName = '';
        $this->shopDescription = '';
        $this->shopImage = '';
        $this->shopStatus = 'draft';

        Flux::modal('edit-shop')->show();
    }

    public function saveShop(): void
    {
        abort_unless($this->isOwner, 403);

        $data = $this->validate([
            'shopName' => ['required', 'string', 'max:255'],
            'shopDescription' => ['nullable', 'string', 'max:1000'],
            'shopImage' => ['nullable', 'string', 'max:2048'],
            'shopStatus' => ['required', Rule::in(array_keys(self::STATUSES))],
        ]);

        if ($this->editingShopId) {
            $shop = $this->campaign->shops()->where('shops.id', $this->editingShopId)->firstOrFail();

            $shop->update([
                'name' => $data['shopName'],
                'description' => $data['shopDescription'] ?: null,
                'image' => $data['shopImage'] ?: null,
                'status' => $data['shopStatus'],
            ]);

            Flux::toast('Shop updated.', variant: 'success');
        } else {
            $shop = Shop::create([
                'name' => $data['shopName'],
                'description' => $data['shopDescription'] ?: null,
                'image' => $data['shopImage'] ?: null,
                'status' => $data['shopStatus'],
                'owner_id' => auth()->id(),
            ]);

            $shop->campaigns()->attach($this->campaign->id);

            $this->statusFilter = $data['shopStatus'];

            Flux::toast('Shop created.', variant: 'success');
        }

        unset($this->shops);

        Flux::modal('edit-shop')->close();
    }

    public function duplicateShop(int $shopId): void
    {
        abort_unless($this->isOwner, 403);

        $shop = $this->campaign->shops()->where('shops.id', $shopId)->with('stock')->first();

        if (! $shop) {
            return;
        }

        $copy = Shop::create([
            'name' => $shop->name.' (Copy)',
            'description' => $shop->description,
            'image' => $shop->image,
            'status' => 'draft',
            'owner_id' => auth()->id(),
        ]);

        $copy->campaigns()->attach($this->campaign->id);

        foreach ($shop->stock as $stock) {
            $copy->stock()->create([
                'item_id' => $stock->item_id,
                'price' => $stock->price,
                'quantity' => $stock->quantity,
            ]);
        }

        $this->statusFilter = 'draft';

        unset($this->shops);

        Flux::toast($shop->name.' duplicated.', variant: 'success');
    }

    /**
     * Only shops belonging to the currently selected campaign are considered accessible.
     */
    #[Computed]
    public function managingStockShop(): ?Shop
    {
        if (! $this->managingStockShopId || ! $this->campaign) {
            return null;
        }

        return $this->campaign->shops()->where('shops.id', $this->managingStockShopId)->first();
    }

    /**
     * @return Collection<int, \App\Models\ShopStock>
     */
    #[Computed]
    public function shopStock(): Collection
    {
        if (! $this->managingStockShop) {
            return collect();
        }

        return $this->managingStockShop->stock()->with('item')->orderBy('id')->get();
    }

    /**
     * Items not already stocked in the shop currently being managed.
     *
     * @return Collection<int, Item>
     */
    #[Computed]
    public function availableItems(): Collection
    {
        $stockedItemIds = $this->shopStock->pluck('item_id');

        return Item::query()->whereNotIn('id', $stockedItemIds)->orderBy('name')->get();
    }

    public function manageStock(int $shopId): void
    {
        abort_unless($this->isOwner, 403);

        $shop = $this->campaign->shops()->where('shops.id', $shopId)->first();

        if (! $shop) {
            return;
        }

        $this->managingStockShopId = $shop->id;
        $this->newStockItemId = '';
        $this->newStockPrice = 0;
        $this->newStockQuantity = 1;

        unset($this->managingStockShop, $this->shopStock, $this->availableItems);

        Flux::modal('manage-stock')->show();
    }

    public function updatedNewStockItemId(string $value): void
    {
        $this->newStockPrice = Item::find($value)?->default_price ?? 0;
    }

    public function addStockItem(): void
    {
        abort_unless($this->isOwner, 403);

        if (! $this->managingStockShop) {
            return;
        }

        $data = $this->validate([
            'newStockItemId' => ['required', 'integer'],
            'newStockPrice' => ['required', 'integer', 'min:0'],
            'newStockQuantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = $this->availableItems->firstWhere('id', (int) $data['newStockItemId']);

        if (! $item) {
            $this->addError('newStockItemId', 'That item is not available to add.');

            return;
        }

        $this->managingStockShop->stock()->create([
            'item_id' => $item->id,
            'price' => $data['newStockPrice'],
            'quantity' => $data['newStockQuantity'],
        ]);

        $this->newStockItemId = '';
        $this->newStockPrice = 0;
        $this->newStockQuantity = 1;

        unset($this->shopStock, $this->availableItems, $this->shops);

        Flux::toast($item->name.' added to shop.', variant: 'success');
    }

    public function removeStockItem(int $stockId): void
    {
        abort_unless($this->isOwner, 403);

        if (! $this->managingStockShop) {
            return;
        }

        $this->managingStockShop->stock()->where('id', $stockId)->delete();

        unset($this->shopStock, $this->availableItems, $this->shops);
    }
};
?>

<div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Shops</flux:heading>
        <flux:badge size="sm">DM</flux:badge>
    </div>

    <div class="mt-4 flex rounded-lg bg-canvas p-1">
        @foreach (['open' => 'Open', 'closed' => 'Closed', 'draft' => 'Draft', 'hidden' => 'Hidden', 'all' => 'All'] as $value => $label)
            <button
                type="button"
                wire:click="setStatusFilter('{{ $value }}')"
                @class([
                    'flex-1 rounded-md py-1.5 text-center text-xs font-bold transition',
                    'bg-surface text-content shadow-sm' => $statusFilter === $value,
                    'text-content-muted' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mt-4 flex flex-col gap-3">
        @forelse ($this->shops as $shop)
            <div wire:key="shop-{{ $shop->id }}" class="rounded-xl border border-line p-3">
                <div class="flex items-center gap-3">
                    @if ($shop->image)
                        <img src="{{ $shop->image }}" alt="{{ $shop->name }}" class="size-10 shrink-0 rounded-lg border border-line object-cover" />
                    @else
                        <flux:avatar size="sm" name="{{ $shop->name }}" color="auto" />
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold text-content">{{ $shop->name }}</div>
                        <div class="text-xs text-content-muted">{{ $shop->stock_count }} item{{ $shop->stock_count === 1 ? '' : 's' }}</div>
                    </div>

                    <flux:badge size="sm" :color="match ($shop->status) {
                        'open' => 'green',
                        'closed' => 'zinc',
                        'hidden' => 'indigo',
                        default => 'amber',
                    }">
                        {{ ucfirst($shop->status) }}
                    </flux:badge>
                </div>

                @if ($this->isOwner)
                    <div class="mt-3 grid grid-cols-4 gap-2">
                        @if ($shop->status === 'open')
                            <flux:button size="sm" variant="primary" wire:click="closeShop({{ $shop->id }})">Close</flux:button>
                        @else
                            <flux:button size="sm" variant="primary" wire:click="openShop({{ $shop->id }})">Open</flux:button>
                        @endif

                        <flux:button size="sm" variant="ghost" wire:click="manageStock({{ $shop->id }})">Items</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="editShop({{ $shop->id }})">Edit</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="duplicateShop({{ $shop->id }})">Duplicate</flux:button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                @if ($statusFilter === 'all')
                    No shops yet.
                @else
                    No {{ $statusFilter }} shops yet.
                @endif
            </div>
        @endforelse
    </div>

    @if ($this->isOwner)
        <flux:button variant="primary" class="mt-5 w-full" wire:click="newShop">
            + New shop
        </flux:button>
    @endif

    <flux:modal name="edit-shop" class="md:w-96">
        <form wire:submit="saveShop" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingShopId ? 'Edit Shop' : 'New Shop' }}</flux:heading>
                <flux:text class="mt-2">
                    {{ $editingShopId ? "Update this shop's details." : 'This shop starts as a draft until you open it to players.' }}
                </flux:text>
            </div>

            <flux:input wire:model="shopName" label="Shop name" placeholder="e.g. The Iron Anvil" />

            <flux:textarea wire:model="shopDescription" label="Description" rows="3" />

            <flux:input wire:model="shopImage" label="Image URL" placeholder="https://..." />

            <flux:select wire:model="shopStatus" label="Status">
                @foreach ($this->statusOptions() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ $editingShopId ? 'Save changes' : 'Create shop' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="manage-stock" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Manage Items</flux:heading>
                <flux:text class="mt-2">{{ $this->managingStockShop?->name }}</flux:text>
            </div>

            <div class="flex flex-col gap-2">
                @forelse ($this->shopStock as $stock)
                    <div wire:key="stock-{{ $stock->id }}" class="flex items-center gap-3 rounded-xl border border-line p-3">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $stock->item->name }}</div>
                            <div class="text-xs text-content-muted">{{ $stock->price }} gp · Qty {{ $stock->quantity }}</div>
                        </div>

                        <flux:button size="sm" variant="ghost" wire:click="removeStockItem({{ $stock->id }})">Remove</flux:button>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-line p-4 text-center text-sm text-content-muted">
                        No items in stock yet.
                    </div>
                @endforelse
            </div>

            <flux:separator />

            <form wire:submit="addStockItem" class="space-y-4">
                <div>
                    <flux:select wire:model.live="newStockItemId" label="Item" placeholder="Choose an item…">
                        @foreach ($this->availableItems as $item)
                            <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newStockItemId" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="newStockPrice" type="number" min="0" label="Price (gp)" />
                    <flux:input wire:model="newStockQuantity" type="number" min="1" label="Quantity" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Add item</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

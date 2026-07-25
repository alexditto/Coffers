<?php

use App\Models\Character;
use App\Models\Inventory;
use App\Models\ItemCount;
use App\Models\Shop;
use App\Models\ShoppingCart;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $shopId;

    /**
     * @var array<int, array{name: string, quantity: int}>
     */
    public array $receiptLines = [];

    public int $receiptTotal = 0;

    public int $receiptBalanceAfter = 0;

    public function mount(int $shopId): void
    {
        $this->shopId = $shopId;
    }

    /**
     * Only an open shop within the user's currently selected, accessible campaign
     * may be viewed - never trust the route id alone.
     */
    #[Computed]
    public function shop(): ?Shop
    {
        $campaignId = session('selected_campaign_id');

        if (! $campaignId) {
            return null;
        }

        $user = auth()->user();

        $campaign = $user->campaigns->merge($user->owned_campaigns)
            ->unique('id')
            ->firstWhere('id', $campaignId);

        if (! $campaign) {
            return null;
        }

        return $campaign->shops()
            ->where('shops.id', $this->shopId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * The current user's own character in the shop's campaign.
     */
    #[Computed]
    public function character(): ?Character
    {
        $campaignId = session('selected_campaign_id');

        if (! $campaignId) {
            return null;
        }

        return auth()->user()->characters()
            ->where('campaign_id', $campaignId)
            ->with('inventory')
            ->first();
    }

    /**
     * @return Collection<int, \App\Models\ShopStock>
     */
    #[Computed]
    public function stock(): Collection
    {
        if (! $this->shop) {
            return collect();
        }

        return $this->shop->stock()->with('item')->orderBy('id')->get();
    }

    /**
     * The character's unpurchased cart entries for this shop. Adding an item to the
     * cart is how it gets "claimed" - the observer on ShoppingCart already decrements
     * the shop stock's quantity as soon as the row is created.
     *
     * @return Collection<int, ShoppingCart>
     */
    #[Computed]
    public function cartItems(): Collection
    {
        if (! $this->character?->inventory) {
            return collect();
        }

        return $this->character->inventory->shopping_carts()
            ->where('is_purchased', false)
            ->whereHas('shop_stock', fn ($query) => $query->where('shop_id', $this->shopId))
            ->with('shop_stock.item')
            ->get();
    }

    /**
     * Cart entries grouped by shop stock line, with quantity and subtotal.
     *
     * @return Collection<int, array{stock: \App\Models\ShopStock, quantity: int, subtotal: int}>
     */
    #[Computed]
    public function cartLines(): Collection
    {
        return $this->cartItems
            ->groupBy('shop_stock_id')
            ->map(fn (Collection $carts) => [
                'stock' => $carts->first()->shop_stock,
                'quantity' => $carts->count(),
                'subtotal' => $carts->first()->shop_stock->price * $carts->count(),
            ])
            ->values();
    }

    #[Computed]
    public function cartTotal(): int
    {
        return $this->cartItems->sum(fn (ShoppingCart $cart) => $cart->shop_stock->price);
    }

    #[Computed]
    public function gold(): int
    {
        return $this->character?->inventory?->gold ?? 0;
    }

    #[Computed]
    public function goldAfter(): int
    {
        return max(0, $this->gold - $this->cartTotal);
    }

    #[Computed]
    public function canAfford(): bool
    {
        return $this->gold >= $this->cartTotal;
    }

    #[Computed]
    public function shortBy(): int
    {
        return max(0, $this->cartTotal - $this->gold);
    }

    public function addToCart(int $shopStockId): void
    {
        if (! $this->shop || ! $this->character) {
            return;
        }

        $stock = $this->shop->stock()->where('id', $shopStockId)->first();

        if (! $stock || $stock->quantity < 1) {
            return;
        }

        $inventory = $this->character->inventory;

        if (! $inventory) {
            $inventory = Inventory::create(['character_id' => $this->character->id, 'gold' => 0]);
            $this->character->update(['inventory_id' => $inventory->id]);
        }

        ShoppingCart::create([
            'shop_stock_id' => $stock->id,
            'inventory_id' => $inventory->id,
            'is_purchased' => false,
        ]);

        unset($this->character, $this->stock, $this->cartItems);
    }

    public function removeFromCart(int $shopStockId): void
    {
        if (! $this->character?->inventory) {
            return;
        }

        $cart = $this->character->inventory->shopping_carts()
            ->where('is_purchased', false)
            ->whereHas('shop_stock', fn ($query) => $query->where('id', $shopStockId)->where('shop_id', $this->shopId))
            ->first();

        if (! $cart) {
            return;
        }

        $cart->delete();

        unset($this->stock, $this->cartItems);
    }

    public function checkout(): void
    {
        if (! $this->character) {
            return;
        }

        Flux::modal('cart-review')->show();
    }

    public function openConfirm(): void
    {
        if (! $this->canAfford || $this->cartItems->isEmpty()) {
            return;
        }

        Flux::modal('cart-review')->close();
        Flux::modal('confirm-purchase')->show();
    }

    public function backToCart(): void
    {
        Flux::modal('confirm-purchase')->close();
        Flux::modal('cart-review')->show();
    }

    public function confirmPurchase(): void
    {
        $inventory = $this->character?->inventory;

        if (! $inventory || ! $this->canAfford || $this->cartItems->isEmpty()) {
            return;
        }

        $lines = $this->cartLines;
        $total = $this->cartTotal;

        DB::transaction(function () use ($inventory, $lines, $total) {
            foreach ($lines as $line) {
                $itemCount = ItemCount::firstOrCreate(
                    ['inventory_id' => $inventory->id, 'item_id' => $line['stock']->item_id],
                    ['count' => 0]
                );

                $itemCount->increment('count', $line['quantity']);
            }

            $inventory->shopping_carts()
                ->where('is_purchased', false)
                ->whereHas('shop_stock', fn ($query) => $query->where('shop_id', $this->shopId))
                ->update(['is_purchased' => true]);

            $inventory->decrement('gold', $total);
        });

        $this->receiptLines = $lines->map(fn (array $line) => [
            'name' => $line['stock']->item->name,
            'quantity' => $line['quantity'],
        ])->all();
        $this->receiptTotal = $total;
        $this->receiptBalanceAfter = $inventory->fresh()->gold;

        unset($this->character, $this->stock, $this->cartItems);

        Flux::modal('confirm-purchase')->close();
        Flux::modal('purchase-success')->show();
    }

    public function keepShopping(): void
    {
        Flux::modal('purchase-success')->close();
    }
};
?>

<div class="flex flex-col gap-4 pb-4">
    <div>
        <flux:button :href="route('shops')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            Back to Shops
        </flux:button>
    </div>

    @if (! $this->shop)
        <div class="rounded-2xl border-2 border-dashed border-line bg-surface p-6 text-center text-sm text-content-muted">
            This shop isn't available right now.
        </div>
    @else
        <div class="relative overflow-hidden rounded-2xl border border-line shadow-sm">
            @if ($this->shop->image)
                <img src="{{ $this->shop->image }}" alt="{{ $this->shop->name }}" class="h-28 w-full object-cover" />
            @else
                <div class="h-28 w-full bg-gradient-to-br from-brand-300 to-brand-600"></div>
            @endif

            <div class="absolute inset-x-0 bottom-0 flex items-end p-3">
                <div class="rounded-xl bg-surface/90 px-3 py-2 shadow-sm backdrop-blur-sm">
                    <div class="text-base font-bold text-content">{{ $this->shop->name }}</div>
                    <div class="text-xs text-content-muted">{{ $this->stock->count() }} item{{ $this->stock->count() === 1 ? '' : 's' }} · Open</div>
                </div>
            </div>
        </div>

        @unless ($this->character)
            <div class="rounded-xl border border-line bg-canvas p-3 text-center text-xs text-content-muted">
                You need a character in this campaign to shop here.
            </div>
        @endunless

        <div class="grid grid-cols-2 gap-3">
            @forelse ($this->stock as $entry)
                <div wire:key="stock-{{ $entry->id }}" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
                    @if ($entry->item->image)
                        <img src="{{ $entry->item->image }}" alt="{{ $entry->item->name }}" class="h-20 w-full object-cover" />
                    @else
                        <div class="flex h-20 w-full items-center justify-center bg-canvas">
                            <flux:icon.cube class="size-6 text-content-faint" />
                        </div>
                    @endif

                    <div class="p-3">
                        <div class="truncate text-sm font-bold text-content">{{ $entry->item->name }}</div>

                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs font-bold text-gold-600">{{ $entry->price }} gp</span>

                            @if ($entry->quantity <= 0)
                                <flux:badge size="sm" color="zinc">Sold out</flux:badge>
                            @elseif ($this->character)
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    square
                                    icon="plus"
                                    wire:click="addToCart({{ $entry->id }})"
                                    aria-label="Add {{ $entry->item->name }} to cart"
                                />
                            @endif
                        </div>

                        @if ($entry->quantity > 0)
                            <div class="mt-1 text-[10px] text-content-faint">{{ $entry->quantity }} left</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-2 rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                    This shop has no items in stock yet.
                </div>
            @endforelse
        </div>

        @if ($this->character)
            <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface p-3 shadow-lg">
                <span class="text-sm font-semibold text-content-muted">
                    Cart · {{ $this->cartItems->count() }} item{{ $this->cartItems->count() === 1 ? '' : 's' }}
                </span>

                <flux:button variant="primary" wire:click="checkout">
                    Checkout · {{ $this->cartTotal }} gp
                </flux:button>
            </div>

            {{-- D1: Cart review --}}
            <flux:modal name="cart-review" class="md:w-96">
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">Cart</flux:heading>
                        <flux:text>{{ $this->shop->name }}</flux:text>
                    </div>

                    <div class="flex flex-col gap-3">
                        @forelse ($this->cartLines as $line)
                            <div wire:key="cart-line-{{ $line['stock']->id }}" class="flex items-center gap-3">
                                @if ($line['stock']->item->image)
                                    <img src="{{ $line['stock']->item->image }}" alt="{{ $line['stock']->item->name }}" class="size-10 shrink-0 rounded-lg border border-line object-cover" />
                                @else
                                    <flux:avatar size="sm" name="{{ $line['stock']->item->name }}" color="auto" />
                                @endif

                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-bold text-content">{{ $line['stock']->item->name }}</div>
                                    <div class="text-xs text-content-muted">{{ $line['stock']->price }} gp each</div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2 rounded-lg border border-line px-2 py-1">
                                    <button type="button" wire:click="removeFromCart({{ $line['stock']->id }})" class="px-1 font-bold text-content-muted hover:text-content">−</button>
                                    <span class="w-4 text-center text-sm font-bold text-content">{{ $line['quantity'] }}</span>
                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $line['stock']->id }})"
                                        @disabled($line['stock']->quantity < 1)
                                        class="px-1 font-bold text-content-muted hover:text-content disabled:cursor-not-allowed disabled:opacity-30"
                                    >+</button>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                                Your cart is empty.
                            </div>
                        @endforelse
                    </div>

                    @if ($this->cartLines->isNotEmpty())
                        <div class="border-t border-dashed border-line pt-3">
                            <div class="flex justify-between text-base font-bold text-content">
                                <span>Total</span>
                                <span>{{ $this->cartTotal }} gp</span>
                            </div>
                        </div>

                        @unless ($this->canAfford)
                            <div class="flex items-start gap-2 rounded-xl border border-line bg-canvas p-3 text-xs text-content-muted">
                                <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                                <span>You're <strong class="text-content">{{ $this->shortBy }} gp</strong> short.</span>
                            </div>
                        @endunless

                        <div class="flex items-center justify-between text-xs text-content-muted">
                            <span>Your gold</span>
                            <span>
                                {{ $this->gold }} gp
                                @if ($this->canAfford)
                                    → <span class="font-bold text-content">{{ $this->goldAfter }} gp</span>
                                @endif
                            </span>
                        </div>

                        <flux:button variant="primary" class="w-full" :disabled="! $this->canAfford" wire:click="openConfirm">
                            Buy · {{ $this->cartTotal }} gp
                        </flux:button>
                    @endif
                </div>
            </flux:modal>

            {{-- D2: Confirm purchase --}}
            <flux:modal name="confirm-purchase" class="md:w-96">
                <div class="space-y-6 text-center">
                    <div>
                        <flux:heading size="lg">Confirm purchase</flux:heading>
                        <flux:text class="mt-1">from {{ $this->shop->name }}</flux:text>
                    </div>

                    <div class="space-y-2 text-left">
                        @foreach ($this->cartLines as $line)
                            <div class="flex justify-between text-sm text-content">
                                <span>{{ $line['stock']->item->name }} ×{{ $line['quantity'] }}</span>
                                <span>{{ $line['subtotal'] }} gp</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between border-t border-line pt-3 text-base font-bold text-content">
                        <span>Total</span>
                        <span>{{ $this->cartTotal }} gp</span>
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-canvas p-3">
                        <div class="text-left">
                            <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Gold After</div>
                            <div class="text-lg font-bold text-content">{{ $this->goldAfter }} gp</div>
                        </div>
                        <div class="text-xs text-content-muted">was {{ $this->gold }}</div>
                    </div>

                    <div class="flex gap-2">
                        <flux:button variant="ghost" class="flex-1" wire:click="backToCart">Cancel</flux:button>
                        <flux:button variant="primary" class="flex-1" wire:click="confirmPurchase">Confirm</flux:button>
                    </div>
                </div>
            </flux:modal>

            {{-- D3: Success / receipt --}}
            <flux:modal name="purchase-success" class="md:w-96">
                <div class="flex flex-col items-center space-y-4 text-center">
                    <div class="flex size-16 items-center justify-center rounded-full border-2 border-brand-600 text-2xl font-bold text-brand-600">✓</div>

                    <div>
                        <flux:heading size="lg">Purchase complete</flux:heading>
                        <flux:text class="mt-1">
                            {{ collect($receiptLines)->sum('quantity') }} item{{ collect($receiptLines)->sum('quantity') === 1 ? '' : 's' }} added to your inventory
                        </flux:text>
                    </div>

                    <div class="w-full space-y-2 rounded-xl border border-line p-3 text-left">
                        @foreach ($receiptLines as $line)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-bold text-content">{{ $line['name'] }}</span>
                                <span class="text-content-muted">×{{ $line['quantity'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex w-full justify-between text-xs text-content-muted">
                        <span>Paid</span>
                        <span class="font-bold text-content">{{ $receiptTotal }} gp · Balance {{ $receiptBalanceAfter }} gp</span>
                    </div>

                    <div class="flex w-full gap-2">
                        <flux:button :href="route('inventory')" wire:navigate variant="ghost" class="flex-1">View inventory</flux:button>
                        <flux:button variant="primary" class="flex-1" wire:click="keepShopping">Keep shopping</flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif
    @endif
</div>

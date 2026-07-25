<?php

namespace App\Models;

use App\Observers\ShoppingCartObserver;
use Database\Factories\ShoppingCartFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ShoppingCartObserver::class)]
class ShoppingCart extends Model
{
    /** @use HasFactory<ShoppingCartFactory> */
    use HasFactory;

    public function shop_stock(): BelongsTo
    {
        return $this->belongsTo(ShopStock::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function isPurchased(): bool
    {
        return $this->is_purchased;
    }

    public function completePurchase(): void
    {
        $this->is_purchased = true;
        $this->save();
    }
}

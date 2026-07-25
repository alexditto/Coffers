<?php

namespace App\Models;

use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    public function item_counts(): HasMany
    {
        return $this->hasMany(ItemCount::class);
    }

    public function shopping_carts(): HasMany
    {
        return $this->hasMany(ShoppingCart::class);
    }
}

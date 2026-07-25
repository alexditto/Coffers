<?php

namespace App\Observers;

use App\Models\ShoppingCart;

class ShoppingCartObserver
{
    /**
     * Handle the ShoppingCart "created" event.
     */
    public function created(ShoppingCart $shoppingCart): void
    {
        $shoppingCart->shop_stock()->decrement('quantity');
    }

    /**
     * Handle the ShoppingCart "updated" event.
     */
    public function updated(ShoppingCart $shoppingCart): void
    {
        //
    }

    /**
     * Handle the ShoppingCart "deleted" event.
     */
    public function deleted(ShoppingCart $shoppingCart): void
    {
        $shoppingCart->shop_stock()->increment('quantity');
    }
}
